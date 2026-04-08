<?php

/**
 * PHPUnit Version Compatibility Test Runner
 *
 * Installs each supported PHPUnit version, generates a coverage report,
 * and validates the JSON output structure.
 *
 * Usage:
 *   php tests/test-compat.php
 *   composer test-compat
 */

declare(strict_types=1);

$versionMatrix = [
    ['phpunit' => '^10.0', 'coverage' => '^10.0', 'label' => 'PHPUnit 10.x'],
    ['phpunit' => '^11.0', 'coverage' => '^11.0', 'label' => 'PHPUnit 11.x'],
    ['phpunit' => '^12.0', 'coverage' => '^12.0', 'label' => 'PHPUnit 12.x'],
    ['phpunit' => '^13.0', 'coverage' => '^13.0', 'label' => 'PHPUnit 13.x'],
];

$restoreVersion = ['phpunit' => '^13.0', 'coverage' => '^13.0'];
$projectRoot = dirname(__DIR__);
$outputDir = $projectRoot . '/tests/output/compat';
$genScript = $projectRoot . '/tests/generate-coverage.php';

function run(string $cmd): array
{
    $output = [];
    exec($cmd . ' 2>&1', $output, $exitCode);
    return ['output' => implode("\n", $output), 'exit' => $exitCode];
}

function msg(string $text, string $type = 'info'): void
{
    $color = match ($type) {
        'info' => "\033[36m",
        'ok' => "\033[32m",
        'fail' => "\033[31m",
        default => "\033[0m",
    };
    echo $color . $text . "\033[0m" . PHP_EOL;
}

function validateJson(string $path): array
{
    if (!file_exists($path)) {
        return ['JSON file not found'];
    }

    $data = json_decode(file_get_contents($path), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['Invalid JSON: ' . json_last_error_msg()];
    }

    $errors = [];

    // meta
    foreach (['format', 'version', 'timestamp', 'generator'] as $key) {
        if (!isset($data['meta'][$key])) {
            $errors[] = "meta.$key missing";
        }
    }

    // summary — all 5 metrics
    foreach (['lines', 'branches', 'paths', 'functions', 'classes'] as $metric) {
        if (!isset($data['summary'][$metric])) {
            $errors[] = "summary.$metric missing";
            continue;
        }
        foreach (['total', 'covered', 'percentage'] as $field) {
            if (!array_key_exists($field, $data['summary'][$metric])) {
                $errors[] = "summary.$metric.$field missing";
            }
        }
    }

    // files
    if (empty($data['files'])) {
        $errors[] = 'files empty';
    } else {
        $first = reset($data['files']);
        foreach (['path', 'lines', 'branches', 'paths', 'functions', 'classes'] as $key) {
            if (!isset($first[$key])) {
                $errors[] = "file entry.$key missing";
            }
        }
    }

    return $errors;
}

// ---- Main ----

echo PHP_EOL;
msg("=== PHPUnit Version Compatibility Test ===");
echo PHP_EOL;

if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $outputDir));
    }
}

$results = [];

foreach ($versionMatrix as $i => $v) {
    $label = $v['label'];
    $jsonFile = $outputDir . "/v$i.json";

    // 1. Install
    msg("[$label] Installing...");
    $r = run("cd $projectRoot && composer require phpunit/phpunit:{$v['phpunit']} phpunit/php-code-coverage:{$v['coverage']} --with-all-dependencies --no-interaction -q");
    if ($r['exit'] !== 0) {
        $results[$label] = ['status' => 'INSTALL FAIL', 'detail' => $r['output']];
        msg("[$label] install failed", 'fail');
        continue;
    }

    // 2. Generate coverage via generate-coverage.php
    msg("[$label] Generating coverage...");
    $r = run("php $genScript $jsonFile");

    if ($r['exit'] !== 0 || !str_contains($r['output'], 'OK')) {
        $results[$label] = ['status' => 'GENERATE FAIL', 'detail' => $r['output']];
        msg("[$label] generate failed", 'fail');
        continue;
    }

    // 3. Validate JSON
    $errors = validateJson($jsonFile);

    if (empty($errors)) {
        $data = json_decode(file_get_contents($jsonFile), true);
        $results[$label] = [
            'status' => 'PASS',
            'phpunit' => $data['meta']['phpunit']['version'] ?? '?',
            'files' => count($data['files']),
            'lines' => $data['summary']['lines']['total'] . ' total, ' . $data['summary']['lines']['covered'] . ' covered',
        ];
        msg("[$label] PASS", 'ok');
    } else {
        $results[$label] = ['status' => 'VALIDATE FAIL', 'detail' => implode('; ', $errors)];
        msg("[$label] validation failed", 'fail');
    }
}

// 4. Restore latest version
msg(PHP_EOL . "Restoring PHPUnit 13.x...");
run("cd $projectRoot && composer require phpunit/phpunit:{$restoreVersion['phpunit']} phpunit/php-code-coverage:{$restoreVersion['coverage']} --with-all-dependencies --no-interaction -q");

// 5. Summary
echo PHP_EOL;
msg("=== Summary ===");
echo PHP_EOL;

$passed = 0;
$failed = 0;
foreach ($results as $label => $r) {
    if ($r['status'] === 'PASS') {
        $passed++;
        msg("  PASS  $label — PHPUnit {$r['phpunit']}, {$r['files']} files", 'ok');
    } else {
        $failed++;
        msg("  FAIL  $label — {$r['status']}", 'fail');
        if (isset($r['detail'])) {
            foreach (explode("\n", $r['detail']) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    echo "        " . $line . PHP_EOL;
                }
            }
        }
    }
}

echo PHP_EOL;
msg("Passed: $passed, Failed: $failed", $failed === 0 ? 'ok' : 'fail');
echo PHP_EOL;

exit($failed > 0 ? 1 : 0);
