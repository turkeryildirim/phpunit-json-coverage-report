<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Util;

/**
 * Shared utility for coverage percentage calculation.
 */
class CoverageCalculator
{
    /**
     * Calculate coverage percentage based on covered and total items.
     *
     * @param int $covered Number of covered items.
     * @param int $total Total number of items.
     * @return float Percentage rounded to 2 decimal places.
     */
    public static function percentage(int $covered, int $total): float
    {
        return $total > 0 ? round(($covered / $total) * 100, 2) : 0.0;
    }
}
