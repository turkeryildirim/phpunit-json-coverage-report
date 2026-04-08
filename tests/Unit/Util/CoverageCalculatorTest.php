<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Turker\PHPUnitCoverageReporter\Util\CoverageCalculator;

/**
 * Unit tests for the CoverageCalculator utility.
 */
#[CoversClass(CoverageCalculator::class)]
class CoverageCalculatorTest extends TestCase
{
    /**
     * Test percentage calculation with various inputs.
     */
    #[Test]
    #[DataProvider('percentageProvider')]
    public function percentage_calculation(int $covered, int $total, float $expected): void
    {
        $this->assertEquals($expected, CoverageCalculator::percentage($covered, $total));
    }

    /**
     * Data provider for percentage calculations.
     */
    public static function percentageProvider(): array
    {
        return [
            '100% coverage' => [10, 10, 100.0],
            '50% coverage'  => [5, 10, 50.0],
            '0% coverage'   => [0, 10, 0.0],
            'zero total'    => [0, 0, 0.0],
            'rounding'      => [1, 3, 33.33],
            'large values'  => [1000, 2000, 50.0],
        ];
    }
}
