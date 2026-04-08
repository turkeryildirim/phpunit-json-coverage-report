<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Feature\Extractor;

use SebastianBergmann\CodeCoverage\Node\File;
use Turker\PHPUnitCoverageReporter\Extractor\LineExtractor;
use Turker\PHPUnitCoverageReporter\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LineExtractor::class)]
class LineExtractorTest extends FeatureTestCase
{
    #[Test]
    public function it_extracts_correct_line_summary(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new LineExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertArrayHasKey('total', $summary);
        $this->assertArrayHasKey('covered', $summary);
        $this->assertArrayHasKey('percentage', $summary);
        $this->assertGreaterThan(0, $summary['total']);
        $this->assertGreaterThan(0, $summary['covered']);
        $this->assertGreaterThanOrEqual(0.0, $summary['percentage']);
        $this->assertLessThanOrEqual(100.0, $summary['percentage']);
    }

    #[Test]
    public function it_matches_php_code_coverage_api_for_summary(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new LineExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertEquals(
            $report->numberOfExecutableLines(),
            $summary['total'],
            'summary total must equal numberOfExecutableLines()'
        );
        $this->assertEquals(
            $report->numberOfExecutedLines(),
            $summary['covered'],
            'summary covered must equal numberOfExecutedLines()'
        );
    }

    #[Test]
    public function it_extracts_file_level_line_data(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new LineExtractor();

        $fileNode = $this->getFirstFileNode($report);
        if ($fileNode === null) {
            $this->markTestSkipped('No file node found in report');
        }

        $fileData = $extractor->extractFileData($fileNode);

        $this->assertArrayHasKey('total', $fileData);
        $this->assertArrayHasKey('covered', $fileData);
        $this->assertArrayHasKey('percentage', $fileData);
        $this->assertArrayHasKey('details', $fileData);
    }

    #[Test]
    public function it_matches_php_code_coverage_api_for_file_data(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new LineExtractor();

        $fileNode = $this->getFirstFileNode($report);
        if ($fileNode === null) {
            $this->markTestSkipped('No file node found in report');
        }

        $fileData = $extractor->extractFileData($fileNode);

        $this->assertEquals(
            $fileNode->numberOfExecutableLines(),
            $fileData['total'],
            'file total must equal numberOfExecutableLines()'
        );
        $this->assertEquals(
            $fileNode->numberOfExecutedLines(),
            $fileData['covered'],
            'file covered must equal numberOfExecutedLines()'
        );
    }

    #[Test]
    public function details_count_equals_total_lines(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new LineExtractor();

        foreach ($report as $node) {
            if ($node instanceof File) {
                $fileData = $extractor->extractFileData($node);
                $this->assertCount(
                    $fileData['total'],
                    $fileData['details'],
                    "details count must equal total for {$node->pathAsString()}"
                );
            }
        }
    }

    #[Test]
    public function it_skips_null_coverage_entries(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new LineExtractor();

        foreach ($report as $node) {
            if ($node instanceof File) {
                $fileData = $extractor->extractFileData($node);
                foreach ($fileData['details'] as $line => $detail) {
                    $this->assertNotNull($detail, "Line $line should not be null");
                }
            }
        }
    }
}
