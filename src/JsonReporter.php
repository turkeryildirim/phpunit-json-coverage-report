<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use Turker\PHPUnitCoverageReporter\Formatter\JsonFormatter;

/**
 * JSON Coverage Reporter for PHPUnit.
 *
 * Reports all 5 coverage metrics: Line, Branch, Path, Function, and Class.
 */
class JsonReporter
{
    /**
     * Current version of the reporter.
     */
    public const VERSION = '1.0.1';

    /**
     * @var JsonFormatter The formatter instance.
     */
    private JsonFormatter $formatter;

    /**
     * Initialize the reporter with an optional formatter.
     *
     * @param JsonFormatter|null $formatter The formatter instance.
     */
    public function __construct(?JsonFormatter $formatter = null)
    {
        $this->formatter = $formatter ?? new JsonFormatter();
    }

    /**
     * Process coverage data and output to JSON file
     *
     * @param CodeCoverage $coverage The coverage data
     * @param string|null $target Output file path (default: coverage.json)
     * @param string[]|null $enabledMetrics List of metrics to include
     * @return void
     * @throws \RuntimeException if output file cannot be written
     */
    public function process(CodeCoverage $coverage, ?string $target = null, ?array $enabledMetrics = null): void
    {
        $data = $this->formatter->format($coverage, $enabledMetrics);
        $output = $target ?? 'coverage.json';

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode coverage data to JSON: ' . json_last_error_msg());
        }

        if (@file_put_contents($output, $json) === false) {
            throw new \RuntimeException(sprintf('Failed to write coverage report to "%s"', $output));
        }
    }
}
