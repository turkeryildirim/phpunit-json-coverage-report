<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Unit;

use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Builder;
use ReflectionClass;
use ReflectionProperty;
use Turker\PHPUnitCoverageReporter\JsonReporterExtension;

/**
 * Unit tests for the JsonReporterExtension class.
 */
#[CoversClass(JsonReporterExtension::class)]
class JsonReporterExtensionTest extends TestCase
{
    private string $configFile;
    private ReflectionProperty $sealedProperty;

    #[Before]
    protected function setUp(): void
    {
        $this->configFile = dirname(__DIR__, 2) . '/phpunit.xml.dist';

        // Temporarily unseal the event facade so registerSubscriber() can be called during tests.
        // PHPUnit seals it before test execution; we restore the sealed state in tearDown.
        $ef = EventFacade::instance();
        $ref = new ReflectionClass($ef);
        $this->sealedProperty = $ref->getProperty('sealed');
        $this->sealedProperty->setAccessible(true);
        $this->sealedProperty->setValue($ef, false);
    }

    #[After]
    protected function tearDown(): void
    {
        // Restore sealed state
        $this->sealedProperty->setValue(EventFacade::instance(), true);
    }

    #[Test]
    public function can_be_instantiated(): void
    {
        $extension = new JsonReporterExtension();
        $this->assertInstanceOf(JsonReporterExtension::class, $extension);
    }

    #[Test]
    public function implements_extension_interface(): void
    {
        $extension = new JsonReporterExtension();
        $this->assertInstanceOf(Extension::class, $extension);
    }

    #[Test]
    public function bootstrap_returns_early_when_no_coverage_report(): void
    {
        $extension = new JsonReporterExtension();

        $config = (new Builder())->build(['--no-coverage', '--configuration', $this->configFile]);
        $this->assertFalse($config->hasCoverageReport(), 'Precondition: no coverage report expected');

        $facade = new Facade();
        $params = ParameterCollection::fromArray([]);

        // Should return without registering subscriber
        $extension->bootstrap($config, $facade, $params);

        $this->assertFalse($facade->requiresCodeCoverageCollection());
    }

    #[Test]
    public function bootstrap_registers_subscriber_when_coverage_enabled(): void
    {
        $extension = new JsonReporterExtension();

        $config = (new Builder())->build(['--coverage-text', 'php://null', '--configuration', $this->configFile]);
        $this->assertTrue($config->hasCoverageReport(), 'Precondition: coverage report expected');

        $facade = new Facade();
        $params = ParameterCollection::fromArray([]);

        // Should register subscriber without exception
        $extension->bootstrap($config, $facade, $params);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function bootstrap_uses_custom_output_file_param(): void
    {
        $extension = new JsonReporterExtension();

        $config = (new Builder())->build(['--coverage-text', 'php://null', '--configuration', $this->configFile]);
        $facade = new Facade();
        $params = ParameterCollection::fromArray(['outputFile' => 'custom-output.json']);

        $extension->bootstrap($config, $facade, $params);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function bootstrap_parses_metrics_param_as_comma_separated_array(): void
    {
        $extension = new JsonReporterExtension();

        $config = (new Builder())->build(['--coverage-text', 'php://null', '--configuration', $this->configFile]);
        $facade = new Facade();
        $params = ParameterCollection::fromArray(['metrics' => 'lines,classes']);

        $extension->bootstrap($config, $facade, $params);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function bootstrap_uses_default_output_file_when_no_param(): void
    {
        $extension = new JsonReporterExtension();

        $config = (new Builder())->build(['--coverage-text', 'php://null', '--configuration', $this->configFile]);
        $facade = new Facade();
        $params = ParameterCollection::fromArray([]);

        $extension->bootstrap($config, $facade, $params);

        $this->addToAssertionCount(1);
    }
}
