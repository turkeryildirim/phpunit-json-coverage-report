<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Integration;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\BranchCoverage;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\FullyCovered;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\FunctionCoverage;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\LineCoverage;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\PartiallyCovered;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\PathCoverage;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\SpecialCharacters;

/**
 * Base class for integration tests providing coverage generation and file management.
 *
 * Coverage objects are generated once in setUpBeforeClass() before PHPUnit's
 * per-test coverage starts, avoiding driver interference within test methods.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static ?CodeCoverage $sharedMinimalCoverage = null;
    protected static ?CodeCoverage $sharedFullCoverage = null;
    protected static ?CodeCoverage $sharedEmptyCoverage = null;

    protected string $outputDir;

    /**
     * Generate shared coverage objects before any test method runs.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$sharedEmptyCoverage === null) {
            self::$sharedEmptyCoverage = self::buildEmptyCoverage();
        }

        if (self::$sharedMinimalCoverage === null) {
            self::$sharedMinimalCoverage = self::buildMinimalCoverage();
        }

        if (self::$sharedFullCoverage === null) {
            self::$sharedFullCoverage = self::buildFullCoverage();
        }
    }

    #[Before]
    protected function setUp(): void
    {
        $this->outputDir = dirname(__DIR__) . '/output/integration-test';
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
    }

    #[After]
    protected function tearDown(): void
    {
        $files = glob($this->outputDir . '/*.json');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Create coverage from a single fixture file with exercise.
     */
    protected function createCoverageFromFixture(string $fixtureName): CodeCoverage
    {
        $fixturesDir = dirname(__DIR__) . '/fixtures';
        $fixtureFile = $fixturesDir . '/' . $fixtureName;

        $filter = new Filter();
        $filter->includeFile($fixtureFile);

        $driver = (new Selector())->forLineCoverage($filter);
        $coverage = new CodeCoverage($driver, $filter);

        $coverage->start('integration-test');
        require_once $fixtureFile;
        $this->exerciseFixture($fixtureName);
        $coverage->stop();

        return $coverage;
    }

    /**
     * Assert that a JSON file contains a valid coverage report structure.
     */
    protected function assertValidJsonReport(string $filePath): void
    {
        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        $this->assertNotNull($data, 'Output should be valid JSON');
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('files', $data);
    }

    /**
     * Assert that a report data array has all 5 coverage metrics.
     */
    protected function assertReportHasAllMetrics(array $data): void
    {
        foreach (['lines', 'branches', 'paths', 'functions', 'classes'] as $metric) {
            $this->assertArrayHasKey($metric, $data['summary'], "summary.$metric missing");
            $this->assertArrayHasKey('total', $data['summary'][$metric]);
            $this->assertArrayHasKey('covered', $data['summary'][$metric]);
            $this->assertArrayHasKey('percentage', $data['summary'][$metric]);
        }
    }

    private static function buildEmptyCoverage(): CodeCoverage
    {
        $filter = new Filter();
        $driver = (new Selector())->forLineCoverage($filter);
        return new CodeCoverage($driver, $filter);
    }

    private static function buildMinimalCoverage(): CodeCoverage
    {
        $filter = new Filter();
        $fixtureFile = dirname(__DIR__) . '/fixtures/LineCoverage.php';
        $filter->includeFile($fixtureFile);

        $driver = (new Selector())->forLineCoverage($filter);
        $coverage = new CodeCoverage($driver, $filter);

        $coverage->start('integration-minimal');
        require_once $fixtureFile;
        $lc = new LineCoverage();
        $lc->covered();
        $coverage->stop();

        return $coverage;
    }

    private static function buildFullCoverage(): CodeCoverage
    {
        $fixturesDir = dirname(__DIR__) . '/fixtures';
        $filter = new Filter();

        foreach (glob($fixturesDir . '/*.php') as $file) {
            $filter->includeFile($file);
        }

        $driver = (new Selector())->forLineCoverage($filter);
        $coverage = new CodeCoverage($driver, $filter);

        $coverage->start('integration-full');

        // Load dependencies first
        $extended = $fixturesDir . '/PhpFeaturesExtended.php';
        if (file_exists($extended)) {
            require_once $extended;
        }
        $features = $fixturesDir . '/PhpFeatures.php';
        if (file_exists($features)) {
            require_once $features;
        }

        foreach (glob($fixturesDir . '/*.php') as $file) {
            require_once $file;
        }

        // Exercise all fixtures
        $lc = new LineCoverage();
        $lc->covered();
        $lc->partiallyCovered();

        $bc = new BranchCoverage();
        $bc->ifElse(true);
        $bc->ifElse(false);
        $bc->switchCase(1);
        $bc->ternary(true);

        $pc = new PathCoverage();
        $pc->nestedBranches(true, false);
        $pc->complexPath(4);

        $fc = new FunctionCoverage();
        $fc->covered();
        $fc->alsoCovered();
        $fc->partiallyCovered();

        $full = new FullyCovered();
        $full->method1();
        $full->method2();
        $full->method3();

        $partial = new PartiallyCovered();
        $partial->covered();
        $partial->alsoCovered();

        $special = new SpecialCharacters();
        $special->unicodeCharacters();
        $special->emojis();

        $coverage->stop();

        return $coverage;
    }

    private function exerciseFixture(string $fixtureName): void
    {
        $classMap = [
            'LineCoverage.php' => fn() => (new LineCoverage())->covered(),
            'BranchCoverage.php' => fn() => (new BranchCoverage())->ifElse(true),
            'FunctionCoverage.php' => fn() => (new FunctionCoverage())->covered(),
            'ClassCoverage.php' => function () {
                $f = new FullyCovered();
                $f->method1();
                $f->method2();
                $f->method3();
            },
        ];

        if (isset($classMap[$fixtureName])) {
            $classMap[$fixtureName]();
        }
    }
}
