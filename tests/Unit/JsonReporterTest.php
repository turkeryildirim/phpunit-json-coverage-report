<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Turker\PHPUnitCoverageReporter\Formatter\JsonFormatter;
use Turker\PHPUnitCoverageReporter\JsonReporter;

/**
 * Unit tests for the JsonReporter class.
 */
#[CoversClass(JsonReporter::class)]
class JsonReporterTest extends TestCase
{
    #[Test]
    public function version_constant_is_semver_string(): void
    {
        $this->assertIsString(JsonReporter::VERSION);
        $this->assertNotEmpty(JsonReporter::VERSION);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', JsonReporter::VERSION);
    }

    #[Test]
    public function constructor_creates_default_formatter_when_null(): void
    {
        $reporter = new JsonReporter();

        $ref = new ReflectionClass($reporter);
        $prop = $ref->getProperty('formatter');

        $this->assertInstanceOf(JsonFormatter::class, $prop->getValue($reporter));
    }

    #[Test]
    public function constructor_accepts_custom_formatter(): void
    {
        $formatter = new JsonFormatter();
        $reporter = new JsonReporter($formatter);

        $ref = new ReflectionClass($reporter);
        $prop = $ref->getProperty('formatter');

        $this->assertSame($formatter, $prop->getValue($reporter));
    }
}
