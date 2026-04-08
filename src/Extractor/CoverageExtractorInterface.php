<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Extractor;

use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\File;

/**
 * Interface for all Coverage Extractors
 */
interface CoverageExtractorInterface
{
    /**
     * Extract summary coverage from a node (Directory or File)
     *
     * @param AbstractNode $node
     * @return array{total: int, covered: int, percentage: float}
     */
    public function extractSummary(AbstractNode $node): array;

    /**
     * Extract detailed coverage data for a specific File node
     *
     * @param File $file
     * @return array{total: int, covered: int, percentage: float, details: array}
     */
    public function extractFileData(File $file): array;
}
