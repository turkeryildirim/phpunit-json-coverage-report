<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Extractor;

use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\File;
use Turker\PHPUnitCoverageReporter\Util\CoverageCalculator;

/**
 * Extracts Path Coverage data from CodeCoverage report nodes
 */
class PathExtractor implements CoverageExtractorInterface
{
    /**
     * Extract summary path coverage from the given report node.
     *
     * @param AbstractNode $node The coverage report node.
     * @return array{total: int, covered: int, percentage: float}
     */
    public function extractSummary(AbstractNode $node): array
    {
        $total = $node->numberOfExecutablePaths();
        $covered = $node->numberOfExecutedPaths();

        return [
            'total' => $total,
            'covered' => $covered,
            'percentage' => CoverageCalculator::percentage($covered, $total),
        ];
    }

    /**
     * Extract detailed path coverage for a specific file.
     *
     * @param File $file The file node.
     * @return array{total: int, covered: int, percentage: float, details: array}
     */
    public function extractFileData(File $file): array
    {
        $total = $file->numberOfExecutablePaths();
        $covered = $file->numberOfExecutedPaths();

        return [
            'total' => $total,
            'covered' => $covered,
            'percentage' => CoverageCalculator::percentage($covered, $total),
            'details' => [],
        ];
    }
}
