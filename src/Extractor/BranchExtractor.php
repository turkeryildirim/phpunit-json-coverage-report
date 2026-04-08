<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Extractor;

use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\File;
use Turker\PHPUnitCoverageReporter\Util\CoverageCalculator;

/**
 * Extracts Branch Coverage data from CodeCoverage report nodes
 */
class BranchExtractor implements CoverageExtractorInterface
{
    /**
     * Extract summary branch coverage from the given report node.
     *
     * @param AbstractNode $node The coverage report node.
     * @return array{total: int, covered: int, percentage: float}
     */
    public function extractSummary(AbstractNode $node): array
    {
        $total = $node->numberOfExecutableBranches();
        $covered = $node->numberOfExecutedBranches();

        return [
            'total' => $total,
            'covered' => $covered,
            'percentage' => CoverageCalculator::percentage($covered, $total),
        ];
    }

    /**
     * Extract detailed branch coverage for a specific file.
     *
     * @param File $file The file node.
     * @return array{total: int, covered: int, percentage: float, details: array}
     */
    public function extractFileData(File $file): array
    {
        $total = $file->numberOfExecutableBranches();
        $covered = $file->numberOfExecutedBranches();

        return [
            'total' => $total,
            'covered' => $covered,
            'percentage' => CoverageCalculator::percentage($covered, $total),
            'details' => [],
        ];
    }
}
