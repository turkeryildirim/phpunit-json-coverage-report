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
    public const VERSION = '1.1.1';

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
     * @throws \RuntimeException if output directory cannot be created or output file cannot be written
     */
    public function process(CodeCoverage $coverage, ?string $target = null, ?array $enabledMetrics = null): void
    {
        $json = $this->encode($this->formatter->format($coverage, $enabledMetrics));
        $output = $target ?? 'coverage.json';

        // Fully qualified: an unqualified frameless call adds an unreachable namespace-lookup branch (PHP 8.4+).
        $this->ensureDirectoryExists(\dirname($output));
        $this->write($output, $json);
    }

    /**
     * Encode report data as pretty-printed JSON.
     *
     * @param array $data The formatted report data.
     * @return string The JSON string.
     * @throws \RuntimeException if the data cannot be encoded
     */
    private function encode(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode coverage data to JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Create the output directory (recursively) when it does not exist.
     *
     * @param string $directory The output directory path.
     * @throws \RuntimeException if the directory cannot be created
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !@mkdir($directory, 0777, true)) {
            throw new \RuntimeException(sprintf('Failed to create output directory "%s"', $directory));
        }
    }

    /**
     * Write the JSON report to the output file.
     *
     * @param string $output The output file path.
     * @param string $json The JSON content.
     * @throws \RuntimeException if the file cannot be written
     */
    private function write(string $output, string $json): void
    {
        if (@file_put_contents($output, $json) === false) {
            throw new \RuntimeException(sprintf('Failed to write coverage report to "%s"', $output));
        }
    }
}
