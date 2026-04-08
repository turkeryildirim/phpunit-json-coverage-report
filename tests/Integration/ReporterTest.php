<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Turker\PHPUnitCoverageReporter\Formatter\JsonFormatter;
use Turker\PHPUnitCoverageReporter\JsonReporter;

/**
 * Integration tests for the JsonReporter class.
 */
#[CoversClass(JsonReporter::class)]
#[CoversClass(JsonFormatter::class)]
class ReporterTest extends IntegrationTestCase
{
    #[Test]
    public function it_writes_valid_json_file(): void
    {
        $outputFile = $this->outputDir . '/test-output.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedMinimalCoverage, $outputFile);

        $this->assertFileExists($outputFile);
        $content = file_get_contents($outputFile);
        $data = json_decode($content, true);
        $this->assertNotNull($data, 'Output should be valid JSON');
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('files', $data);
    }

    #[Test]
    public function it_uses_default_filename_when_null(): void
    {
        $reporter = new JsonReporter();

        $cwd = getcwd();
        chdir($this->outputDir);
        try {
            $reporter->process(self::$sharedMinimalCoverage);
            $this->assertFileExists($this->outputDir . '/coverage.json');
        } finally {
            chdir($cwd);
            @unlink($this->outputDir . '/coverage.json');
        }
    }

    #[Test]
    public function it_throws_on_unwritable_path(): void
    {
        $reporter = new JsonReporter();

        $this->expectException(RuntimeException::class);
        $reporter->process(self::$sharedMinimalCoverage, '/nonexistent/dir/that/cannot/exist/coverage.json');
    }

    #[Test]
    public function meta_contains_required_fields(): void
    {
        $outputFile = $this->outputDir . '/meta-test.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedMinimalCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);
        $meta = $data['meta'];

        $this->assertEquals('phpunit-json-coverage', $meta['format']);
        $this->assertEquals(JsonReporter::VERSION, $meta['version']);
        $this->assertArrayHasKey('timestamp', $meta);
        $this->assertArrayHasKey('generator', $meta);
        $this->assertEquals('phpunit-json-coverage-report', $meta['generator']['name']);
        $this->assertEquals(JsonReporter::VERSION, $meta['generator']['version']);
    }

    #[Test]
    public function meta_phpunit_version_at_correct_path(): void
    {
        $outputFile = $this->outputDir . '/phpunit-meta-test.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedMinimalCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);

        $this->assertArrayHasKey('phpunit', $data['meta']);
        $this->assertArrayHasKey('version', $data['meta']['phpunit']);
        $this->assertArrayHasKey('coverageLibrary', $data['meta']['phpunit']);
        $this->assertArrayNotHasKey('meta', $data['meta'], 'PHPUnit info should not be nested under meta.meta');
    }

    #[Test]
    public function version_constant_exists_and_is_valid(): void
    {
        $this->assertNotEmpty(JsonReporter::VERSION);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', JsonReporter::VERSION);
    }

    #[Test]
    public function it_accepts_custom_formatter(): void
    {
        $outputFile = $this->outputDir . '/custom-formatter.json';

        $formatter = new JsonFormatter();
        $reporter = new JsonReporter($formatter);
        $reporter->process(self::$sharedMinimalCoverage, $outputFile);

        $this->assertFileExists($outputFile);
        $data = json_decode(file_get_contents($outputFile), true);
        $this->assertNotNull($data);
    }

    #[Test]
    public function it_supports_custom_output_path(): void
    {
        $customDir = $this->outputDir . '/custom/nested/dir';
        if (!is_dir($customDir)) {
            mkdir($customDir, 0777, true);
        }
        $outputFile = $customDir . '/my-report.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedMinimalCoverage, $outputFile);

        $this->assertFileExists($outputFile);
        $data = json_decode(file_get_contents($outputFile), true);
        $this->assertNotNull($data);

        @unlink($outputFile);
        @rmdir($customDir);
        @rmdir(dirname($customDir));
        @rmdir(dirname($customDir, 2));
    }

    #[Test]
    public function output_file_extension_not_required(): void
    {
        $outputFile = $this->outputDir . '/no-extension-report';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedMinimalCoverage, $outputFile);

        $this->assertFileExists($outputFile);
        $data = json_decode(file_get_contents($outputFile), true);
        $this->assertNotNull($data);

        @unlink($outputFile);
    }

    #[Test]
    public function it_filters_metrics_in_process_method(): void
    {
        $outputFile = $this->outputDir . '/filtered.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedMinimalCoverage, $outputFile, ['lines']);

        $data = json_decode(file_get_contents($outputFile), true);

        $this->assertArrayHasKey('lines', $data['summary']);
        $this->assertArrayNotHasKey('branches', $data['summary']);
        $this->assertArrayNotHasKey('paths', $data['summary']);
        $this->assertArrayNotHasKey('functions', $data['summary']);
        $this->assertArrayNotHasKey('classes', $data['summary']);
    }

    #[Test]
    public function process_with_all_fixtures_produces_complete_report(): void
    {
        $outputFile = $this->outputDir . '/full-report.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);

        $this->assertNotNull($data);
        $this->assertNotEmpty($data['files']);
        $this->assertGreaterThan(0, $data['summary']['lines']['total']);
        $this->assertGreaterThan(0, $data['summary']['lines']['covered']);
    }

    #[Test]
    public function process_with_unicode_content_handles_encoding(): void
    {
        $outputFile = $this->outputDir . '/unicode-report.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile);

        $content = file_get_contents($outputFile);
        $this->assertNotFalse($content);

        $data = json_decode($content, true);
        $this->assertNotNull($data, 'JSON should be valid');

        $this->assertStringContainsString("\n", $content);
        $this->assertStringNotContainsString('\\/', $content);
        $this->assertTrue(mb_check_encoding($content, 'UTF-8'), 'Output should be valid UTF-8');
    }
}
