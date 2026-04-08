<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Formatter;

use PHPUnit\Runner\Version;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;
use Turker\PHPUnitCoverageReporter\Extractor\LineExtractor;
use Turker\PHPUnitCoverageReporter\Extractor\BranchExtractor;
use Turker\PHPUnitCoverageReporter\Extractor\PathExtractor;
use Turker\PHPUnitCoverageReporter\Extractor\FunctionExtractor;
use Turker\PHPUnitCoverageReporter\Extractor\ClassExtractor;

use Turker\PHPUnitCoverageReporter\Extractor\CoverageExtractorInterface;
use Turker\PHPUnitCoverageReporter\JsonReporter;

/**
 * Formats coverage data into JSON structure using CodeCoverage::getReport() API.
 */
class JsonFormatter
{
    /**
     * @var array<string, CoverageExtractorInterface> Active extractors for each metric.
     */
    private array $extractors;

    /**
     * Initialize formatter with default or custom extractors.
     *
     * @param array<string, CoverageExtractorInterface>|null $extractors Custom extractors (null for defaults).
     */
    public function __construct(?array $extractors = null)
    {
        $this->extractors = $extractors ?? [
            'lines' => new LineExtractor(),
            'branches' => new BranchExtractor(),
            'paths' => new PathExtractor(),
            'functions' => new FunctionExtractor(),
            'classes' => new ClassExtractor(),
        ];
    }

    /**
     * Format coverage data into JSON structure
     *
     * @param CodeCoverage $coverage
     * @param string[]|null $enabledMetrics List of metrics to include (null for all)
     * @return array
     */
    public function format(CodeCoverage $coverage, ?array $enabledMetrics = null): array
    {
        $report = $coverage->getReport();
        $extractors = $this->getFilteredExtractors($enabledMetrics);

        return [
            'meta' => $this->formatMeta(),
            'summary' => $this->formatSummary($report, $extractors),
            'files' => $this->formatFiles($report, $extractors),
        ];
    }

    /**
     * Format metadata section for the report.
     *
     * @return array Metadata structure.
     */
    private function formatMeta(): array
    {
        $data = [
            'format' => 'phpunit-json-coverage',
            'version' => JsonReporter::VERSION,
            'timestamp' => date('c'),
            'generator' => [
                'name' => 'phpunit-json-coverage-report',
                'version' => JsonReporter::VERSION,
            ],
        ];

        if (class_exists(Version::class)) {
            $data['phpunit']['version'] = Version::id();
        }

        if (class_exists(\SebastianBergmann\CodeCoverage\Version::class)) {
            $data['phpunit']['coverageLibrary'] = \SebastianBergmann\CodeCoverage\Version::id();
        }

        return $data;
    }

    /**
     * Format summary metrics from report root (Directory node).
     *
     * @param Directory $report The root directory node.
     * @param array<string, CoverageExtractorInterface> $extractors Active extractors.
     * @return array Summary data structure.
     */
    private function formatSummary(Directory $report, array $extractors): array
    {
        return array_map(static function ($extractor) use ($report) {
            return $extractor->extractSummary($report);
        }, $extractors);
    }

    /**
     * Format per-file data from report.
     *
     * @param Directory $report The root directory node.
     * @param array<string, CoverageExtractorInterface> $extractors Active extractors.
     * @return array Files data structure.
     */
    private function formatFiles(Directory $report, array $extractors): array
    {
        $files = [];

        foreach ($this->collectFileNodes($report) as $file) {
            $path = $file->pathAsString();
            $fileData = ['path' => $path];

            foreach ($extractors as $key => $extractor) {
                $fileData[$key] = $extractor->extractFileData($file);
            }

            $files[$path] = $fileData;
        }

        return $files;
    }

    /**
     * Filter extractors based on enabled metrics.
     *
     * @param string[]|null $enabledMetrics List of metrics to include.
     * @return array<string, CoverageExtractorInterface> Filtered extractors.
     */
    private function getFilteredExtractors(?array $enabledMetrics): array
    {
        if ($enabledMetrics === null) {
            return $this->extractors;
        }

        return array_intersect_key($this->extractors, array_flip($enabledMetrics));
    }

    /**
     * Recursively collect all File nodes from the report tree.
     *
     * @param Directory $directory The directory node to traverse.
     * @return iterable<File> Generator of file nodes.
     */
    private function collectFileNodes(Directory $directory): iterable
    {
        foreach ($directory as $node) {
            if ($node instanceof File) {
                yield $node;
            } elseif ($node instanceof Directory) {
                yield from $this->collectFileNodes($node);
            }
        }
    }
}
