<?php

namespace Turker\PHPUnitCoverageReporter\Tests\fixtures;
class PathCoverage
{
    public function nestedBranches(bool $a, bool $b): string
    {
        if ($a) {
            if ($b) {
                return 'both';
            } else {
                return 'a only';
            }
        } else {
            if ($b) {
                return 'b only';
            } else {
                return 'none';
            }
        }
    }

    public function complexPath(int $x): int
    {
        if ($x > 0) {
            if ($x % 2 == 0) {
                return $x * 2;
            } else {
                return $x * 3;
            }
        } elseif ($x < 0) {
            return abs($x);
        } else {
            return 0;
        }
    }

    public function multiplePaths(int $a, int $b, int $c): string
    {
        if ($a > 0) {
            if ($b > 0) {
                return 'a and b positive';
            } elseif ($c > 0) {
                return 'a and c positive';
            } else {
                return 'only a positive';
            }
        } elseif ($b > 0 && $c > 0) {
            return 'b and c positive';
        } else {
            return 'none positive';
        }
    }
}
