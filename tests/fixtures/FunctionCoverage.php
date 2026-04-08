<?php

namespace Turker\PHPUnitCoverageReporter\Tests\fixtures;
class FunctionCoverage
{
    public function covered(): string
    {
        return 'covered';
    }

    public function alsoCovered(): string
    {
        return 'also covered';
    }

    public function notCovered(): string
    {
        return 'not covered';
    }

    public function partiallyCovered(): string
    {
        $result = 'partial';
        if ($result === 'partial') {
            return 'yes';
        }
        return 'no';  // This line might not be covered
    }
}
