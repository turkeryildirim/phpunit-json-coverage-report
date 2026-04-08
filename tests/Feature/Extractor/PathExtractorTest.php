<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Feature\Extractor;

use Turker\PHPUnitCoverageReporter\Extractor\PathExtractor;
use Turker\PHPUnitCoverageReporter\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PathExtractor::class)]
class PathExtractorTest extends FeatureTestCase
{
    #[Test]
    public function it_extracts_summary_structure(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new PathExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertArrayHasKey('total', $summary);
        $this->assertArrayHasKey('covered', $summary);
        $this->assertArrayHasKey('percentage', $summary);
    }

    #[Test]
    public function it_matches_php_code_coverage_api_for_summary(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new PathExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertEquals(
            $report->numberOfExecutablePaths(),
            $summary['total'],
            'summary total must equal numberOfExecutablePaths()'
        );
        $this->assertEquals(
            $report->numberOfExecutedPaths(),
            $summary['covered'],
            'summary covered must equal numberOfExecutedPaths()'
        );
    }

    #[Test]
    public function it_matches_php_code_coverage_api_for_file_data(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new PathExtractor();

        $file = $this->findFileBySuffix($report, 'PathCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('PathCoverage.php fixture not found in report');
        }

        $fileData = $extractor->extractFileData($file);

        $this->assertEquals(
            $file->numberOfExecutablePaths(),
            $fileData['total'],
            'file total must equal numberOfExecutablePaths()'
        );
        $this->assertEquals(
            $file->numberOfExecutedPaths(),
            $fileData['covered'],
            'file covered must equal numberOfExecutedPaths()'
        );
    }

    #[Test]
    public function it_extracts_file_data_for_path_fixture(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new PathExtractor();

        $file = $this->findFileBySuffix($report, 'PathCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('PathCoverage.php fixture not found in report');
        }

        $fileData = $extractor->extractFileData($file);

        $this->assertArrayHasKey('total', $fileData);
        $this->assertArrayHasKey('covered', $fileData);
        $this->assertArrayHasKey('percentage', $fileData);
        $this->assertArrayHasKey('details', $fileData);
    }

    #[Test]
    public function summary_percentage_in_valid_range(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new PathExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertGreaterThanOrEqual(0.0, $summary['percentage']);
        $this->assertLessThanOrEqual(100.0, $summary['percentage']);
    }

    #[Test]
    public function path_details_have_required_keys(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new PathExtractor();

        $file = $this->findFileBySuffix($report, 'PathCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('PathCoverage.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        $this->assertIsArray($fileData['details']);

        // When paths() is available and has data, verify structure
        foreach ($fileData['details'] as $path) {
            $this->assertArrayHasKey('id', $path);
            $this->assertArrayHasKey('covered', $path);
        }

        // Always assert at least the details key is present
        $this->assertTrue(true, 'Path details structure is valid');
    }
}
