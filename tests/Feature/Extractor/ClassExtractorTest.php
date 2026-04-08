<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Feature\Extractor;

use Turker\PHPUnitCoverageReporter\Extractor\ClassExtractor;
use Turker\PHPUnitCoverageReporter\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ClassExtractor::class)]
class ClassExtractorTest extends FeatureTestCase
{
    #[Test]
    public function it_extracts_summary_structure(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new ClassExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertArrayHasKey('total', $summary);
        $this->assertArrayHasKey('covered', $summary);
        $this->assertArrayHasKey('percentage', $summary);
    }

    #[Test]
    public function it_matches_php_code_coverage_api_for_summary(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new ClassExtractor();

        $summary = $extractor->extractSummary($report);

        $expectedTotal = $report->numberOfClasses() + $report->numberOfTraits();
        $expectedCovered = $report->numberOfTestedClasses() + $report->numberOfTestedTraits();

        $this->assertEquals(
            $expectedTotal,
            $summary['total'],
            'summary total must equal numberOfClasses() + numberOfTraits()'
        );
        $this->assertEquals(
            $expectedCovered,
            $summary['covered'],
            'summary covered must equal numberOfTestedClasses() + numberOfTestedTraits()'
        );
    }

    #[Test]
    public function it_matches_php_code_coverage_api_for_file_data(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new ClassExtractor();

        $file = $this->findFileBySuffix($report, 'ClassCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('ClassCoverage.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        $expectedTotal = $file->numberOfClasses() + $file->numberOfTraits();
        $expectedCovered = $file->numberOfTestedClasses() + $file->numberOfTestedTraits();

        $this->assertEquals(
            $expectedTotal,
            $fileData['total'],
            'file total must equal numberOfClasses() + numberOfTraits()'
        );
        $this->assertEquals(
            $expectedCovered,
            $fileData['covered'],
            'file covered must equal numberOfTestedClasses() + numberOfTestedTraits()'
        );
    }

    #[Test]
    public function summary_has_detected_classes(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new ClassExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertGreaterThan(0, $summary['total'], 'Should detect at least one class or trait');
    }

    #[Test]
    public function it_extracts_file_data_with_details(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new ClassExtractor();

        $file = $this->findFileBySuffix($report, 'ClassCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('ClassCoverage.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        $this->assertArrayHasKey('total', $fileData);
        $this->assertArrayHasKey('covered', $fileData);
        $this->assertArrayHasKey('percentage', $fileData);
        $this->assertArrayHasKey('details', $fileData);
        $this->assertGreaterThan(0, $fileData['total']);
    }

    #[Test]
    public function class_detail_structure_is_valid(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new ClassExtractor();

        $file = $this->findFileBySuffix($report, 'ClassCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('ClassCoverage.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        foreach ($fileData['details'] as $class) {
            $this->assertArrayHasKey('name', $class);
            $this->assertArrayHasKey('type', $class);
            $this->assertArrayHasKey('namespace', $class);
            $this->assertArrayHasKey('startLine', $class);
            $this->assertArrayHasKey('executableLines', $class);
            $this->assertArrayHasKey('executedLines', $class);
            $this->assertArrayHasKey('coverage', $class);
            $this->assertArrayHasKey('methods', $class);
            $this->assertArrayHasKey('covered', $class);
            $this->assertContains($class['type'], ['class', 'trait']);
        }
    }

    #[Test]
    public function method_detail_structure_is_valid(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new ClassExtractor();

        $file = $this->findFileBySuffix($report, 'ClassCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('ClassCoverage.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        foreach ($fileData['details'] as $class) {
            foreach ($class['methods'] as $method) {
                $this->assertArrayHasKey('name', $method);
                $this->assertArrayHasKey('signature', $method);
                $this->assertArrayHasKey('startLine', $method);
                $this->assertArrayHasKey('endLine', $method);
                $this->assertArrayHasKey('executableLines', $method);
                $this->assertArrayHasKey('executedLines', $method);
                $this->assertArrayHasKey('coverage', $method);
                $this->assertArrayHasKey('ccn', $method);
            }
        }
    }

    #[Test]
    public function covered_and_uncovered_classes_detected(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new ClassExtractor();

        $file = $this->findFileBySuffix($report, 'ClassCoverage.php');
        if ($file === null) {
            $this->markTestSkipped('ClassCoverage.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        // Should have multiple classes (FullyCovered, PartiallyCovered, NotCovered)
        $this->assertGreaterThan(1, $fileData['total']);

        // Verify covered boolean is set for each class
        foreach ($fileData['details'] as $class) {
            $this->assertIsBool($class['covered']);
        }
    }

    #[Test]
    public function percentage_is_always_in_range(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new ClassExtractor();

        $summary = $extractor->extractSummary($report);

        $this->assertGreaterThanOrEqual(0.0, $summary['percentage']);
        $this->assertLessThanOrEqual(100.0, $summary['percentage']);
    }

    #[Test]
    public function it_processes_files_with_traits(): void
    {
        $report = $this->getFixtureReport();
        $extractor = new ClassExtractor();

        // PhpFeatures.php contains a trait (Timestampable) — this ensures the traits loop executes
        $file = $this->findFileBySuffix($report, 'PhpFeatures.php');
        if ($file === null) {
            $this->markTestSkipped('PhpFeatures.php fixture not found');
        }

        $fileData = $extractor->extractFileData($file);

        $this->assertArrayHasKey('total', $fileData);
        $this->assertArrayHasKey('details', $fileData);

        $types = array_column($fileData['details'], 'type');
        $this->assertContains('trait', $types, 'PhpFeatures.php should contain a trait');
    }
}
