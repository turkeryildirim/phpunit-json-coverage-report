<?php

namespace Turker\PHPUnitCoverageReporter\Tests\fixtures;
class BranchCoverage
{
    public function ifElse(bool $condition): string
    {
        if ($condition) {
            return 'yes';
        } else {
            return 'no';
        }
    }

    public function switchCase(int $value): string
    {
        switch ($value) {
            case 1:
                return 'one';
            case 2:
                return 'two';
            default:
                return 'other';
        }
    }

    public function ternary(bool $cond): string
    {
        return $cond ? 'true' : 'false';
    }

    public function multipleConditions(int $x, int $y): string
    {
        if ($x > 0 && $y > 0) {
            return 'both positive';
        } elseif ($x > 0) {
            return 'x positive';
        } elseif ($y > 0) {
            return 'y positive';
        } else {
            return 'both negative or zero';
        }
    }
}
