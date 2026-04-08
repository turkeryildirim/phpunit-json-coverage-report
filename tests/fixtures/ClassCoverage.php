<?php

namespace Turker\PHPUnitCoverageReporter\Tests\fixtures;
class FullyCovered
{
    public function method1(): string
    {
        return 'method1';
    }

    public function method2(): string
    {
        return 'method2';
    }

    public function method3(): int
    {
        return 42;
    }
}

class PartiallyCovered
{
    public function covered(): string
    {
        return 'covered';
    }

    public function alsoCovered(): int
    {
        return 100;
    }

    public function notCovered(): string
    {
        return 'not covered';
    }
}

class NotCovered
{
    public function method1(): string
    {
        return 'method1';
    }

    public function method2(): string
    {
        return 'method2';
    }

    public function method3(): string
    {
        return 'method3';
    }
}
