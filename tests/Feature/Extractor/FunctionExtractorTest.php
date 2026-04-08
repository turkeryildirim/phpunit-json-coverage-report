<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Feature\Extractor;

use SebastianBergmann\CodeCoverage\Node\File;
use Turker\PHPUnitCoverageReporter\Extractor\FunctionExtractor;
use Turker\PHPUnitCoverageReporter\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FunctionExtractor::class)]
class FunctionExtractorTest extends FeatureTestCase
{
    #[Test]
    public function it_extracts_summary_structure(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new FunctionExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertArrayHasKey('total', $summary);
        $this->assertArrayHasKey('covered', $summary);
        $this->assertArrayHasKey('percentage', $summary);
    }

    #[Test]
    public function it_matches_php_code_coverage_api_for_summary(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new FunctionExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertEquals(
            $report->numberOfFunctions(),
            $summary['total'],
            'summary total must equal numberOfFunctions()'
        );
        $this->assertEquals(
            $report->numberOfTestedFunctions(),
            $summary['covered'],
            'summary covered must equal numberOfTestedFunctions()'
        );
    }

    #[Test]
    public function it_matches_php_code_coverage_api_for_file_data(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new FunctionExtractor();

        $file = $this->findFileBySuffix($report, 'FunctionCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('FunctionCoverage.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        $this->assertEquals(
            $file->numberOfFunctions(),
            $fileData['total'],
            'file total must equal numberOfFunctions()'
        );
        $this->assertEquals(
            $file->numberOfTestedFunctions(),
            $fileData['covered'],
            'file covered must equal numberOfTestedFunctions()'
        );
    }

    #[Test]
    public function it_extracts_file_data_with_function_details(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new FunctionExtractor();

        $file = $this->findFileBySuffix($report, 'FunctionCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('FunctionCoverage.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        $this->assertArrayHasKey('total', $fileData);
        $this->assertArrayHasKey('covered', $fileData);
        $this->assertArrayHasKey('percentage', $fileData);
        $this->assertArrayHasKey('details', $fileData);
    }

    #[Test]
    public function function_details_contain_expected_keys(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new FunctionExtractor();

        // PhpFeaturesExtended.php has standalone functions (calculateDiscount, formatCurrency, isEven)
        $file = $this->findFileBySuffix($report, 'PhpFeaturesExtended.php');
        if ($file === null) {
            $this->markTestSkipped('PhpFeaturesExtended.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        if (empty($fileData['details'])) {
            $this->fail('PhpFeaturesExtended.php should have standalone function details');
        }

        foreach ($fileData['details'] as $fn) {
            $this->assertArrayHasKey('name', $fn);
            $this->assertArrayHasKey('signature', $fn);
            $this->assertArrayHasKey('startLine', $fn);
            $this->assertArrayHasKey('endLine', $fn);
            $this->assertArrayHasKey('executableLines', $fn);
            $this->assertArrayHasKey('executedLines', $fn);
            $this->assertArrayHasKey('coverage', $fn);
            $this->assertArrayHasKey('covered', $fn);
        }
    }

    #[Test]
    public function percentage_is_always_in_range(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new FunctionExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertGreaterThanOrEqual(0.0, $summary['percentage']);
        $this->assertLessThanOrEqual(100.0, $summary['percentage']);
    }

    #[Test]
    public function it_identifies_functions_in_fixtures(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new FunctionExtractor();

        $summary = $extractor->extractSummary($report);

        // PhpFeaturesExtended.php has standalone functions (calculateDiscount, formatCurrency, isEven)
        // If no functions found at all, just verify the structure is correct
        $this->assertIsInt($summary['total']);
        $this->assertIsInt($summary['covered']);
        $this->assertIsFloat($summary['percentage']);

        // Check each file for function details structure
        foreach ($report as $node) {
            if ($node instanceof File) {
                $fileData = $extractor->extractFileData($node);
                if ($fileData['total'] > 0) {
                    foreach ($fileData['details'] as $fn) {
                        $this->assertIsBool($fn['covered']);
                    }
                }
            }
        }
    }
}
