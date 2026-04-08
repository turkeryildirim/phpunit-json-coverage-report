<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter;

use Exception;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Runner\CodeCoverage;

/**
 * Subscriber that generates JSON coverage report when test execution finishes.
 *
 * It listens for the ExecutionFinished event and uses the CodeCoverage runner
 * to obtain metrics and write the report to the target file.
 */
class JsonReporterSubscriber implements ExecutionFinishedSubscriber
{
    /**
     * @param string $outputFile Target output file path.
     * @param string[]|null $enabledMetrics List of enabled metrics to report (null for all).
     */
    public function __construct(
        private readonly string $outputFile,
        private readonly ?array $enabledMetrics
    ) {}

    /**
     * Respond to ExecutionFinished event by generating the coverage report.
     *
     * @param ExecutionFinished $event The event object.
     */
    public function notify(ExecutionFinished $event): void
    {
        if (!class_exists(CodeCoverage::class) || !CodeCoverage::instance()->isActive()) {
            return;
        }

        $coverage = CodeCoverage::instance()->codeCoverage();
        $reporter = new JsonReporter();

        try {
            $reporter->process($coverage, $this->outputFile, $this->enabledMetrics);
        } catch (Exception $e) {
            fwrite(STDERR, sprintf(
                "[JsonReporterExtension] Failed to generate coverage report: %s\n",
                $e->getMessage()
            ));
        }
    }
}
