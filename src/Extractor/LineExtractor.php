<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Extractor;

use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\File;
use Turker\PHPUnitCoverageReporter\Util\CoverageCalculator;

/**
 * Extracts Line Coverage data from CodeCoverage report nodes
 */
class LineExtractor implements CoverageExtractorInterface
{
    /**
     * Extract summary line coverage from the given report node.
     *
     * @param AbstractNode $node The coverage report node.
     * @return array{total: int, covered: int, percentage: float}
     */
    public function extractSummary(AbstractNode $node): array
    {
        $total = $node->numberOfExecutableLines();
        $covered = $node->numberOfExecutedLines();

        return [
            'total' => $total,
            'covered' => $covered,
            'percentage' => CoverageCalculator::percentage($covered, $total),
        ];
    }

    /**
     * Extract detailed line coverage for a specific file.
     *
     * @param File $file The file node.
     * @return array{total: int, covered: int, percentage: float, details: array}
     */
    public function extractFileData(File $file): array
    {
        $total = $file->numberOfExecutableLines();
        $covered = $file->numberOfExecutedLines();

        $details = [];
        $lineData = $file->lineCoverageData();

        foreach ($lineData as $lineNumber => $coverage) {
            if ($coverage === null) {
                continue;
            }

            $details[$lineNumber] = [
                'hits' => is_array($coverage) ? count($coverage) : (int) $coverage,
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
