<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use SebastianBergmann\CodeCoverage\Report\Clover;
use Turker\PHPUnitCoverageReporter\Formatter\JsonFormatter;
use Turker\PHPUnitCoverageReporter\JsonReporter;

/**
 * End-to-end tests for full report generation pipeline.
 */
#[CoversClass(JsonReporter::class)]
#[CoversClass(JsonFormatter::class)]
class FullReportTest extends IntegrationTestCase
{
    #[Test]
    public function full_report_contains_all_five_metrics(): void
    {
        $outputFile = $this->outputDir . '/full-report.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);

        foreach (['lines', 'branches', 'paths', 'functions', 'classes'] as $metric) {
            $this->assertArrayHasKey($metric, $data['summary'], "summary.$metric missing");
            $this->assertArrayHasKey('total', $data['summary'][$metric]);
            $this->assertArrayHasKey('covered', $data['summary'][$metric]);
            $this->assertArrayHasKey('percentage', $data['summary'][$metric]);
        }
    }

    #[Test]
    public function full_report_meta_has_all_required_fields(): void
    {
        $outputFile = $this->outputDir . '/full-meta.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);
        $meta = $data['meta'];

        $this->assertEquals('phpunit-json-coverage', $meta['format']);
        $this->assertNotEmpty($meta['version']);
        $this->assertNotEmpty($meta['timestamp']);
        $this->assertEquals('phpunit-json-coverage-report', $meta['generator']['name']);
        $this->assertArrayHasKey('phpunit', $meta);
    }

    #[Test]
    public function full_report_files_section_lists_all_fixture_files(): void
    {
        $outputFile = $this->outputDir . '/full-files.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);

        $this->assertGreaterThan(0, count($data['files']));

        foreach ($data['files'] as $path => $fileData) {
            $this->assertArrayHasKey('path', $fileData);
            $this->assertEquals($path, $fileData['path']);
        }
    }

    #[Test]
    public function full_report_json_is_valid_and_pretty_printed(): void
    {
        $outputFile = $this->outputDir . '/full-pretty.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile);

        $content = file_get_contents($outputFile);

        $data = json_decode($content, true);
        $this->assertNotNull($data, 'Output should be valid JSON');

        $this->assertStringContainsString("\n", $content);

        if (str_contains($content, '/')) {
            $this->assertStringNotContainsString('\/', $content, 'Slashes should not be escaped');
        }
    }

    #[Test]
    public function full_report_line_coverage_has_non_zero_totals(): void
    {
        $outputFile = $this->outputDir . '/full-lines.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);

        $this->assertGreaterThan(0, $data['summary']['lines']['total'], 'Total lines should be > 0');
        $this->assertGreaterThan(0, $data['summary']['lines']['covered'], 'Covered lines should be > 0');
        $this->assertGreaterThan(0.0, $data['summary']['lines']['percentage']);
    }

    #[Test]
    public function full_report_with_metric_filtering(): void
    {
        $outputFile = $this->outputDir . '/full-filtered.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile, ['lines', 'classes']);

        $data = json_decode(file_get_contents($outputFile), true);

        $this->assertArrayHasKey('lines', $data['summary']);
        $this->assertArrayHasKey('classes', $data['summary']);
        $this->assertArrayNotHasKey('branches', $data['summary']);
        $this->assertArrayNotHasKey('paths', $data['summary']);
        $this->assertArrayNotHasKey('functions', $data['summary']);
    }

    #[Test]
    public function summary_equals_sum_of_all_file_metrics(): void
    {
        $outputFile = $this->outputDir . '/full-consistency.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);

        $totals = ['lines' => 0, 'functions' => 0, 'classes' => 0];
        $covered = ['lines' => 0, 'functions' => 0, 'classes' => 0];

        foreach ($data['files'] as $fileData) {
            foreach (['lines', 'functions', 'classes'] as $metric) {
                if (isset($fileData[$metric])) {
                    $totals[$metric] += $fileData[$metric]['total'];
                    $covered[$metric] += $fileData[$metric]['covered'];
                }
            }
        }

        foreach (['lines', 'functions', 'classes'] as $metric) {
            $this->assertEquals(
                $data['summary'][$metric]['total'],
                $totals[$metric],
                "summary.$metric.total must equal sum of all file $metric totals"
            );
            $this->assertEquals(
                $data['summary'][$metric]['covered'],
                $covered[$metric],
                "summary.$metric.covered must equal sum of all file $metric covered"
            );
        }
    }

    #[Test]
    public function summary_line_counts_match_php_code_coverage_report(): void
    {
        $outputFile = $this->outputDir . '/full-api-check.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);
        $report = self::$sharedFullCoverage->getReport();

        $this->assertEquals(
            $report->numberOfExecutableLines(),
            $data['summary']['lines']['total'],
            'lines.total must equal getReport()->numberOfExecutableLines()'
        );
        $this->assertEquals(
            $report->numberOfExecutedLines(),
            $data['summary']['lines']['covered'],
            'lines.covered must equal getReport()->numberOfExecutedLines()'
        );
        $this->assertEquals(
            $report->numberOfFunctions(),
            $data['summary']['functions']['total'],
            'functions.total must equal getReport()->numberOfFunctions()'
        );
        $this->assertEquals(
            $report->numberOfTestedFunctions(),
            $data['summary']['functions']['covered'],
            'functions.covered must equal getReport()->numberOfTestedFunctions()'
        );
        $this->assertEquals(
            $report->numberOfClasses() + $report->numberOfTraits(),
            $data['summary']['classes']['total'],
            'classes.total must equal getReport()->numberOfClasses() + numberOfTraits()'
        );
        $this->assertEquals(
            $report->numberOfTestedClasses() + $report->numberOfTestedTraits(),
            $data['summary']['classes']['covered'],
            'classes.covered must equal getReport()->numberOfTestedClasses() + numberOfTestedTraits()'
        );
    }

    #[Test]
    public function summary_line_counts_match_clover_xml(): void
    {
        $outputFile = $this->outputDir . '/full-clover-check.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedFullCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);

        $cloverXml = (new Clover())->process(self::$sharedFullCoverage);
        $xml = simplexml_load_string($cloverXml);

        $cloverTotal = (int) $xml->project->metrics['statements'];
        $cloverCovered = (int) $xml->project->metrics['coveredstatements'];

        $this->assertEquals(
            $cloverTotal,
            $data['summary']['lines']['total'],
            'lines.total must match Clover XML statements count'
        );
        $this->assertEquals(
            $cloverCovered,
            $data['summary']['lines']['covered'],
            'lines.covered must match Clover XML coveredstatements count'
        );
    }
}
