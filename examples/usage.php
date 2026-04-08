<?php

/**
 * Example usage of PHPUnit JSON Coverage Reporter
 *
 * This example shows how to programmatically generate a JSON coverage report
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Turker\PHPUnitCoverageReporter\JsonReporter;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;

// 1. Create a filter to specify which files to include in coverage
$filter = new Filter();
$filter->addDirectoryToWhitelist(__DIR__ . '/../src');

// 2. Create the CodeCoverage object
$coverage = new CodeCoverage(
    (new Selector())->forLineCoverage($filter),
    $filter
);

// 3. Run your tests and collect coverage data
// ... your test code here ...

// 4. Generate the JSON coverage report
$reporter = new JsonReporter();
$reporter->process($coverage, __DIR__ . '/coverage.json');

echo "Coverage report generated: " . __DIR__ . '/coverage.json' . PHP_EOL;

// 5. Read and display summary
$coverageData = json_decode(file_get_contents(__DIR__ . '/coverage.json'), true);

echo PHP_EOL . "=== Coverage Summary ===" . PHP_EOL;
foreach ($coverageData['summary'] as $metric => $data) {
    echo ucfirst($metric) . ": {$data['covered']}/{$data['total']} ({$data['percentage']}%)" . PHP_EOL;
}
