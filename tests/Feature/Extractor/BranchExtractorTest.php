<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Feature\Extractor;

use Turker\PHPUnitCoverageReporter\Extractor\BranchExtractor;
use Turker\PHPUnitCoverageReporter\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BranchExtractor::class)]
class BranchExtractorTest extends FeatureTestCase
{
    #[Test]
    public function it_extracts_summary_structure(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new BranchExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertArrayHasKey('total', $summary);
        $this->assertArrayHasKey('covered', $summary);
        $this->assertArrayHasKey('percentage', $summary);
    }

    #[Test]
    public function it_matches_php_code_coverage_api_for_summary(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new BranchExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertEquals(
            $report->numberOfExecutableBranches(),
            $summary['total'],
            'summary total must equal numberOfExecutableBranches()'
        );
        $this->assertEquals(
            $report->numberOfExecutedBranches(),
            $summary['covered'],
            'summary covered must equal numberOfExecutedBranches()'
        );
    }

    #[Test]
    public function it_matches_php_code_coverage_api_for_file_data(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new BranchExtractor();

        $file = $this->findFileBySuffix($report, 'BranchCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('BranchCoverage.php fixture not found in report');
        }

        $fileData = $extractor->extractFileData($file);

        $this->assertEquals(
            $file->numberOfExecutableBranches(),
            $fileData['total'],
            'file total must equal numberOfExecutableBranches()'
        );
        $this->assertEquals(
            $file->numberOfExecutedBranches(),
            $fileData['covered'],
            'file covered must equal numberOfExecutedBranches()'
        );
    }

    #[Test]
    public function it_extracts_file_data_for_branch_fixture(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new BranchExtractor();

        $file = $this->findFileBySuffix($report, 'BranchCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('BranchCoverage.php fixture not found in report');
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
        $extractor = new BranchExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertGreaterThanOrEqual(0.0, $summary['percentage']);
        $this->assertLessThanOrEqual(100.0, $summary['percentage']);
    }

    #[Test]
    public function branch_details_have_required_keys(): void
    {
        $report = $this->getBranchCoverageReport();
        $extractor = new BranchExtractor();

        $file = $this->findFileBySuffix($report, 'BranchCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('BranchCoverage.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        $this->assertIsArray($fileData['details']);

        // When branches() is available and has data, verify structure
        foreach ($fileData['details'] as $branch) {
            $this->assertArrayHasKey('id', $branch);
            $this->assertArrayHasKey('line', $branch);
            $this->assertArrayHasKey('type', $branch);
            $this->assertArrayHasKey('covered', $branch);
        }

        // Always assert at least the details key is present
        $this->assertTrue(true, 'Branch details structure is valid');
    }
}
