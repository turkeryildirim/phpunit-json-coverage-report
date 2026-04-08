<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Turker\PHPUnitCoverageReporter\Formatter\JsonFormatter;
use Turker\PHPUnitCoverageReporter\JsonReporter;
use Turker\PHPUnitCoverageReporter\Util\CoverageCalculator;

/**
 * Integration tests for error handling and edge cases.
 */
#[CoversClass(JsonReporter::class)]
#[CoversClass(JsonFormatter::class)]
#[CoversClass(CoverageCalculator::class)]
class ErrorHandlingTest extends IntegrationTestCase
{
    #[Test]
    public function it_throws_on_unwritable_path(): void
    {
        $reporter = new JsonReporter();

        $this->expectException(RuntimeException::class);
        $reporter->process(self::$sharedEmptyCoverage, '/nonexistent/impossible/path/coverage.json');
    }

    #[Test]
    public function it_produces_valid_structure_on_empty_coverage(): void
    {
        $outputFile = $this->outputDir . '/empty-coverage.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedEmptyCoverage, $outputFile);

        $data = json_decode(file_get_contents($outputFile), true);
        $this->assertNotNull($data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('files', $data);

        foreach (['lines', 'branches', 'paths', 'functions', 'classes'] as $metric) {
            $this->assertEquals(0, $data['summary'][$metric]['total']);
            $this->assertEquals(0, $data['summary'][$metric]['covered']);
            $this->assertEquals(0.0, $data['summary'][$metric]['percentage']);
        }
    }

    #[Test]
    public function unknown_metric_filter_returns_valid_subset(): void
    {
        $outputFile = $this->outputDir . '/unknown-metric.json';

        $reporter = new JsonReporter();
        $reporter->process(self::$sharedEmptyCoverage, $outputFile, ['lines', 'nonexistent_metric']);

        $data = json_decode(file_get_contents($outputFile), true);
        $this->assertArrayHasKey('lines', $data['summary']);
        $this->assertArrayNotHasKey('nonexistent_metric', $data['summary']);
        $this->assertArrayNotHasKey('branches', $data['summary']);
    }

    #[Test]
    public function coverage_calculator_handles_zero_division(): void
    {
        $this->assertEquals(0.0, CoverageCalculator::percentage(0, 0));
        $this->assertEquals(0.0, CoverageCalculator::percentage(5, 0));
    }

    #[Test]
    public function coverage_calculator_computes_correct_percentage(): void
    {
        $this->assertEquals(50.0, CoverageCalculator::percentage(1, 2));
        $this->assertEquals(100.0, CoverageCalculator::percentage(10, 10));
        $this->assertEquals(33.33, CoverageCalculator::percentage(1, 3));
    }

    #[Test]
    public function formatter_works_with_custom_extractors(): void
    {
        $formatter = new JsonFormatter([]);

        $result = $formatter->format(self::$sharedEmptyCoverage);

        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('files', $result);
        $this->assertEmpty($result['summary']);
    }
}
