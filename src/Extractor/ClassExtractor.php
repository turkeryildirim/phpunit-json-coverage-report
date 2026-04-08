<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Extractor;

use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\File;
use Turker\PHPUnitCoverageReporter\Util\CoverageCalculator;

/**
 * Extracts Class and Trait Coverage data from CodeCoverage report nodes.
 *
 * A class is considered covered when all its methods are covered.
 */
class ClassExtractor implements CoverageExtractorInterface
{
    use DataAccessTrait;

    /**
     * Extract summary class and trait coverage from the given report node.
     *
     * @param AbstractNode $node The coverage report node.
     * @return array{total: int, covered: int, percentage: float}
     */
    public function extractSummary(AbstractNode $node): array
    {
        $total = $node->numberOfClasses() + $node->numberOfTraits();
        $covered = $node->numberOfTestedClasses() + $node->numberOfTestedTraits();

        return [
            'total' => $total,
            'covered' => $covered,
            'percentage' => CoverageCalculator::percentage($covered, $total),
        ];
    }

    /**
     * Extract detailed class and trait coverage for a specific file.
     *
     * @param File $file The file node.
     * @return array{total: int, covered: int, percentage: float, details: array}
     */
    public function extractFileData(File $file): array
    {
        // Use the same counting logic as File::numberOfClasses/Traits() so that
        // per-file totals sum correctly to the summary (which uses the same API).
        // numberOfClasses() only counts classes that have at least one method with
        // executable lines; classes with no executable methods are excluded.
        $total = $file->numberOfClasses() + $file->numberOfTraits();

        // numberOfTestedClasses() already combines tested classes AND tested traits
        // (php-code-coverage internally increments numTestedClasses for both).
        // numberOfTestedTraits() is always 0 in the current implementation.
        $covered = $file->numberOfTestedClasses() + $file->numberOfTestedTraits();

        $details = [];

        foreach ($file->classes() as $name => $class) {
            $executableLines = $this->getValue($class, 'executableLines', 0);
            $coverage = $this->getValue($class, 'coverage', 0);
            $isCovered = $executableLines > 0 && $coverage === 100;
            $details[] = $this->buildDetail($name, 'class', $class, $isCovered);
        }

        foreach ($file->traits() as $name => $trait) {
            $executableLines = $this->getValue($trait, 'executableLines', 0);
            $coverage = $this->getValue($trait, 'coverage', 0);
            $isCovered = $executableLines > 0 && $coverage === 100;
            $details[] = $this->buildDetail($name, 'trait', $trait, $isCovered);
        }

        return [
            'total' => $total,
            'covered' => $covered,
            'percentage' => CoverageCalculator::percentage($covered, $total),
            'details' => $details,
        ];
    }

    /**
     * Build a detail entry for a class or trait.
     *
     * @param string $name The name of the class/trait.
     * @param string $type The type ('class' or 'trait').
     * @param object|array $data The raw coverage data for the class/trait.
     * @param bool $isCovered Whether the class/trait is considered fully covered.
     * @return array Formatted detail entry.
     */
    private function buildDetail(string $name, string $type, object|array $data, bool $isCovered): array
    {
        return [
            'name' => $this->getValue($data, 'className', $name),
            'type' => $type,
            'namespace' => $this->getValue($data, 'namespace', ''),
            'startLine' => $this->getValue($data, 'startLine', 0),
            'executableLines' => $this->getValue($data, 'executableLines', 0),
            'executedLines' => $this->getValue($data, 'executedLines', 0),
            'coverage' => $this->getValue($data, 'coverage', 0.0),
            'methods' => $this->extractMethodDetails($this->getValue($data, 'methods', [])),
            'covered' => $isCovered,
        ];
    }

    /**
     * Extract detailed coverage for individual methods.
     *
     * @param iterable $methods Iterable of method coverage data.
     * @return array List of formatted method detail entries.
     */
    private function extractMethodDetails(iterable $methods): array
    {
        $result = [];
        foreach ($methods as $name => $method) {
            $result[] = [
                'name' => $this->getValue($method, 'methodName', $name),
                'signature' => $this->getValue($method, 'signature', ''),
                'startLine' => $this->getValue($method, 'startLine', 0),
                'endLine' => $this->getValue($method, 'endLine', 0),
                'executableLines' => $this->getValue($method, 'executableLines', 0),
                'executedLines' => $this->getValue($method, 'executedLines', 0),
                'coverage' => $this->getValue($method, 'coverage', 0.0),
                'ccn' => $this->getValue($method, 'ccn', 1),
            ];
        }
        return $result;
    }
}
