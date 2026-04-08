<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Unit\Extractor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Turker\PHPUnitCoverageReporter\Extractor\LineExtractor;
use Turker\PHPUnitCoverageReporter\Extractor\BranchExtractor;
use Turker\PHPUnitCoverageReporter\Extractor\PathExtractor;
use Turker\PHPUnitCoverageReporter\Extractor\FunctionExtractor;
use Turker\PHPUnitCoverageReporter\Extractor\ClassExtractor;

/**
 * Basic unit tests for Extractor classes.
 */
#[CoversClass(LineExtractor::class)]
#[CoversClass(BranchExtractor::class)]
#[CoversClass(PathExtractor::class)]
#[CoversClass(FunctionExtractor::class)]
#[CoversClass(ClassExtractor::class)]
class ExtractorInstantiationTest extends TestCase
{
    #[Test]
    public function all_extractors_can_be_instantiated(): void
    {
        $this->assertInstanceOf(LineExtractor::class, new LineExtractor());
        $this->assertInstanceOf(BranchExtractor::class, new BranchExtractor());
        $this->assertInstanceOf(PathExtractor::class, new PathExtractor());
        $this->assertInstanceOf(FunctionExtractor::class, new FunctionExtractor());
        $this->assertInstanceOf(ClassExtractor::class, new ClassExtractor());
    }
}
