<?php

namespace Turker\PHPUnitCoverageReporter\Tests\fixtures;
class LineCoverage
{
    public function covered(): int
    {
        $x = 10;
        $y = 20;
        return $x + $y;
    }

    public function notCovered(): int
    {
        $a = 5;
        $b = 10;
        return $a * $b;
    }

    public function partiallyCovered(): int
    {
        $sum = 0;
        for ($i = 0; $i < 5; $i++) {
            $sum += $i;
        }
        return $sum;
    }
}
