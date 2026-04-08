<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Extractor;

use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\File;
use Turker\PHPUnitCoverageReporter\Util\CoverageCalculator;

/**
 * Extracts Function and Method Coverage data from CodeCoverage report nodes.
 *
 * A function is considered covered when all its executable lines are covered.
 */
class FunctionExtractor implements CoverageExtractorInterface
{
    use DataAccessTrait;

    /**
     * Extract summary function coverage from the given report node.
     *
     * @param AbstractNode $node The coverage report node.
     * @return array{total: int, covered: int, percentage: float}
     */
    public function extractSummary(AbstractNode $node): array
    {
        $total = $node->numberOfFunctions();
        $covered = $node->numberOfTestedFunctions();

        return [
            'total' => $total,
            'covered' => $covered,
            'percentage' => CoverageCalculator::percentage($covered, $total),
        ];
    }

    /**
     * Extract detailed function and method coverage for a specific file.
     *
     * @param File $file The file node.
     * @return array{total: int, covered: int, percentage: float, details: array}
     */
    public function extractFileData(File $file): array
    {
        $functions = $file->functions();
        $total = count($functions);
        $covered = 0;
        $details = [];

        foreach ($functions as $name => $fn) {
            $isCovered = $this->getValue($fn, 'coverage', 0) === 100;

            if ($isCovered) {
                $covered++;
            }

            $details[] = [
                'name' => $this->getValue($fn, 'functionName', $name),
                'signature' => $this->getValue($fn, 'signature', ''),
                'startLine' => $this->getValue($fn, 'startLine', 0),
                'endLine' => $this->getValue($fn, 'endLine', 0),
                'executableLines' => $this->getValue($fn, 'executableLines', 0),
                'executedLines' => $this->getValue($fn, 'executedLines', 0),
                'coverage' => $this->getValue($fn, 'coverage', 0.0),
                'covered' => $isCovered,
            ];
        }

        return [
            'total' => $total,
            'covered' => $covered,
            'percentage' => CoverageCalculator::percentage($covered, $total),
            'details' => $details,
        ];
    }
}
