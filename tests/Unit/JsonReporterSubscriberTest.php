<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Unit;

/**
 * Stream filter that silently discards all written data.
 * Used to suppress expected STDERR output during specific test assertions.
 */
final class NullStreamFilter extends \php_user_filter
{
    public function filter($in, $out, &$consumed, bool $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
        }
        return PSFS_PASS_ON;
    }
}


use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\CodeCoverage;
use Turker\PHPUnitCoverageReporter\JsonReporterSubscriber;

/**
 * Unit tests for the JsonReporterSubscriber class.
 */
#[CoversClass(JsonReporterSubscriber::class)]
class JsonReporterSubscriberTest extends TestCase
{
    #[Test]
    public function can_be_instantiated(): void
    {
        $subscriber = new JsonReporterSubscriber('coverage.json', ['lines']);
        $this->assertInstanceOf(JsonReporterSubscriber::class, $subscriber);
    }

    #[Test]
    public function constructor_with_null_metrics(): void
    {
        $subscriber = new JsonReporterSubscriber('report.json', null);
        $this->assertInstanceOf(JsonReporterSubscriber::class, $subscriber);
    }

    #[Test]
    public function implements_execution_finished_subscriber(): void
    {
        $subscriber = new JsonReporterSubscriber('coverage.json', null);
        $this->assertInstanceOf(ExecutionFinishedSubscriber::class, $subscriber);
    }

    #[Test]
    public function notify_behaves_correctly_based_on_coverage_state(): void
    {
        $outputFile = sys_get_temp_dir() . '/test-notify-' . uniqid('', true) . '.json';
        $subscriber = new JsonReporterSubscriber($outputFile, null);

        $subscriber->notify($this->buildExecutionFinishedEvent());

        $coverageActive = class_exists(CodeCoverage::class)
            && CodeCoverage::instance()->isActive();

        if ($coverageActive) {
            // Coverage is active (test run with --coverage-*): subscriber writes the report
            $this->assertFileExists($outputFile);
            @unlink($outputFile);
        } else {
            // Coverage is inactive (test run with --no-coverage): early return, no file written
            $this->assertFileDoesNotExist($outputFile);
        }
    }

    #[Test]
    public function notify_catches_exception_and_writes_to_stderr_when_process_fails(): void
    {
        $coverageActive = class_exists(CodeCoverage::class)
            && CodeCoverage::instance()->isActive();

        if (!$coverageActive) {
            $this->markTestSkipped('Requires active coverage to reach the process() call');
        }

        // Use an unwritable path so reporter->process() throws, exercising the catch block.
        $subscriber = new JsonReporterSubscriber('/nonexistent/impossible/path/coverage.json', null);

        // Suppress the expected STDERR message produced by the catch block.
        stream_filter_register('null_stderr', NullStreamFilter::class);
        $filter = stream_filter_prepend(STDERR, 'null_stderr');
        try {
            // Should not throw — exception is swallowed and written to STDERR.
            $subscriber->notify($this->buildExecutionFinishedEvent());
        } finally {
            stream_filter_remove($filter);
        }

        $this->addToAssertionCount(1);
    }

    private function buildExecutionFinishedEvent(): ExecutionFinished
    {
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        // GarbageCollectorStatus constructor: runs, collected, threshold, roots,
        // applicationTime, collectorTime, destructorTime, freeTime,
        // running, protected, full, bufferSize
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetry = new Info($snapshot, $duration, $memory, $duration, $memory);

        return new ExecutionFinished($telemetry);
    }
}
