<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Unit\Formatter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Turker\PHPUnitCoverageReporter\Extractor\CoverageExtractorInterface;
use Turker\PHPUnitCoverageReporter\Formatter\JsonFormatter;

/**
 * Unit tests for the JsonFormatter class.
 */
#[CoversClass(JsonFormatter::class)]
class JsonFormatterTest extends TestCase
{
    #[Test]
    public function constructor_uses_default_extractors(): void
    {
        $formatter = new JsonFormatter();
        $this->assertInstanceOf(JsonFormatter::class, $formatter);
    }

    #[Test]
    public function constructor_accepts_custom_extractors(): void
    {
        $formatter = new JsonFormatter([
            'lines' => $this->createStub(CoverageExtractorInterface::class),
        ]);
        $this->assertInstanceOf(JsonFormatter::class, $formatter);
    }
}
