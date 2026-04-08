<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * PHPUnit Extension for automatic JSON coverage report generation.
 *
 * Compatible with PHPUnit 10+ Event System.
 */
class JsonReporterExtension implements Extension
{
    /**
     * Bootstrap the extension by registering a subscriber if coverage is enabled.
     *
     * @param Configuration $configuration The current PHPUnit configuration.
     * @param Facade $facade The facade to register subscribers.
     * @param ParameterCollection $parameters Extension parameters from phpunit.xml.
     */
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        if (!$configuration->hasCoverageReport()) {
            return;
        }

        $outputFile = $parameters->has('outputFile')
            ? $parameters->get('outputFile')
            : 'coverage.json';

        $enabledMetrics = $parameters->has('metrics')
            ? explode(',', $parameters->get('metrics'))
            : null;

        $facade->registerSubscriber(
            new JsonReporterSubscriber($outputFile, $enabledMetrics)
        );
    }
}
