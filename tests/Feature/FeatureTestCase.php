<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Feature;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\NoCodeCoverageDriverWithPathCoverageSupportAvailableException;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\BlogPost;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\BranchCoverage;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\ClassWithEmptyMethods;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\ClassWithMagicMethods;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\EmptyClass;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\FullyCovered;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\FunctionCoverage;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\LineCoverage;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\PartiallyCovered;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\PathCoverage;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\SpecialCharacters;
use function Turker\PHPUnitCoverageReporter\Tests\fixtures\calculateDiscount;
use function Turker\PHPUnitCoverageReporter\Tests\fixtures\formatCurrency;
use function Turker\PHPUnitCoverageReporter\Tests\fixtures\isEven;

/**
 * Base class for Feature tests that need real coverage data from fixtures.
 *
 * Coverage data is generated once in setUpBeforeClass() before PHPUnit's
 * per-test coverage starts, avoiding interference with the coverage driver.
 */
abstract class FeatureTestCase extends TestCase
{
    protected static ?Directory $lineReport = null;
    protected static ?Directory $branchReport = null;
    protected static bool $pathCoverageAvailable = true;

    /**
     * Generate fixture coverage data before any test method runs.
     * This runs outside PHPUnit's per-test coverage window, so the internal
     * start()/stop() calls do not conflict with coverage collection.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $fixturesDir = dirname(__DIR__) . '/fixtures';

        if (self::$lineReport === null) {
            $filter = self::buildFilter($fixturesDir);
            $driver = (new Selector())->forLineCoverage($filter);
            $coverage = new CodeCoverage($driver, $filter);
            self::runFixtures($coverage, $fixturesDir);
            self::$lineReport = $coverage->getReport();
        }

        if (self::$branchReport === null) {
            $filter = self::buildFilter($fixturesDir);
            try {
                $driver = (new Selector())->forLineAndPathCoverage($filter);
                self::$pathCoverageAvailable = true;
            } catch (NoCodeCoverageDriverWithPathCoverageSupportAvailableException) {
                $driver = (new Selector())->forLineCoverage($filter);
                self::$pathCoverageAvailable = false;
            }
            $coverage = new CodeCoverage($driver, $filter);
            self::runFixtures($coverage, $fixturesDir);
            self::$branchReport = $coverage->getReport();
        }
    }

    /**
     * Return the pre-generated line coverage report.
     */
    protected function getFixtureReport(): Directory
    {
        return self::$lineReport;
    }

    /**
     * Return the pre-generated branch/path coverage report.
     * Skips the test if no path coverage driver is available.
     */
    protected function getBranchCoverageReport(): Directory
    {
        if (!self::$pathCoverageAvailable) {
            $this->markTestSkipped('No path coverage driver available (requires Xdebug)');
        }

        return self::$branchReport;
    }

    /**
     * Find a file node by its filename suffix in a report.
     */
    protected function findFileBySuffix(Directory $report, string $suffix): ?File
    {
        return $this->searchDirectory($report, $suffix);
    }

    /**
     * Get the first file node from the report tree.
     */
    protected function getFirstFileNode(Directory $report): ?File
    {
        foreach ($report as $node) {
            if ($node instanceof File) {
                return $node;
            }
            if ($node instanceof Directory) {
                $found = $this->getFirstFileNode($node);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }

    private static function buildFilter(string $fixturesDir): Filter
    {
        $filter = new Filter();
        foreach (glob($fixturesDir . '/*.php') as $file) {
            $filter->includeFile($file);
        }
        return $filter;
    }

    private static function runFixtures(CodeCoverage $coverage, string $fixturesDir): void
    {
        $coverage->start('feature-test');

        // Load dependencies first
        $extended = $fixturesDir . '/PhpFeaturesExtended.php';
        if (file_exists($extended)) {
            require_once $extended;
        }
        $features = $fixturesDir . '/PhpFeatures.php';
        if (file_exists($features)) {
            require_once $features;
        }

        // Load all other fixtures
        foreach (glob($fixturesDir . '/*.php') as $file) {
            require_once $file;
        }

        // LineCoverage
        $lc = new LineCoverage();
        $lc->covered();
        $lc->partiallyCovered();

        // BranchCoverage
        $bc = new BranchCoverage();
        $bc->ifElse(true);
        $bc->ifElse(false);
        $bc->switchCase(1);
        $bc->switchCase(2);
        $bc->ternary(true);
        $bc->ternary(false);
        $bc->multipleConditions(1, 1);
        $bc->multipleConditions(1, -1);

        // PathCoverage
        $pc = new PathCoverage();
        $pc->nestedBranches(true, true);
        $pc->nestedBranches(true, false);
        $pc->nestedBranches(false, true);
        $pc->nestedBranches(false, false);
        $pc->complexPath(4);
        $pc->complexPath(3);
        $pc->complexPath(-1);
        $pc->complexPath(0);

        // FunctionCoverage
        $fc = new FunctionCoverage();
        $fc->covered();
        $fc->alsoCovered();
        $fc->partiallyCovered();

        // ClassCoverage
        $full = new FullyCovered();
        $full->method1();
        $full->method2();
        $full->method3();

        $partial = new PartiallyCovered();
        $partial->covered();
        $partial->alsoCovered();

        // EdgeCases
        $empty = new EmptyClass();
        $withEmptyMethods = new ClassWithEmptyMethods();
        $withEmptyMethods->emptyMethod();
        $withEmptyMethods->methodWithReturn();

        $special = new SpecialCharacters();
        $special->unicodeCharacters();
        $special->emojis();

        $magic = new ClassWithMagicMethods();
        $magic->__set('key', 'value');
        $magic->__get('key');

        // BlogPost / Timestampable trait — exercise ALL trait methods so the trait
        // reaches 100% coverage and the $covered++ branch in ClassExtractor fires.
        $blog = new BlogPost('title', 'content');
        $blog->setCreatedAt(new DateTimeImmutable());
        $blog->setUpdatedAt(new DateTimeImmutable());
        $blog->getCreatedAt();
        $blog->getUpdatedAt();

        // Standalone functions (PhpFeaturesExtended.php)
        calculateDiscount(100.0, 20.0);
        formatCurrency(50.0);
        isEven(4);

        $coverage->stop();
    }

    private function searchDirectory(Directory $dir, string $suffix): ?File
    {
        foreach ($dir as $node) {
            if ($node instanceof File && str_ends_with($node->pathAsString(), $suffix)) {
                return $node;
            }
            if ($node instanceof Directory) {
                $found = $this->searchDirectory($node, $suffix);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }
}
