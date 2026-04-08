<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Feature\Formatter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use Turker\PHPUnitCoverageReporter\Extractor\ClassExtractor;
use Turker\PHPUnitCoverageReporter\Extractor\LineExtractor;
use Turker\PHPUnitCoverageReporter\Formatter\JsonFormatter;
use Turker\PHPUnitCoverageReporter\JsonReporter;
use Turker\PHPUnitCoverageReporter\Tests\Feature\FeatureTestCase;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\LineCoverage;
use Turker\PHPUnitCoverageReporter\Util\CoverageCalculator;

/**
 * Feature tests for the JsonFormatter class with real coverage data.
 */
#[CoversClass(JsonFormatter::class)]
#[CoversClass(JsonReporter::class)]
#[CoversClass(LineExtractor::class)]
#[CoversClass(ClassExtractor::class)]
#[CoversClass(CoverageCalculator::class)]
class JsonFormatterTest extends FeatureTestCase
{
    /** Pre-built coverage with files from two directories (forces nested Directory nodes). */
    protected static ?CodeCoverage $nestedDirCoverage = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$nestedDirCoverage === null) {
            $filter = new Filter();
            $fixtureFile = dirname(__DIR__, 2) . '/fixtures/LineCoverage.php';
            $srcFile = dirname(__DIR__, 3) . '/src/JsonReporter.php';
            $filter->includeFile($fixtureFile);
            $filter->includeFile($srcFile);

            $driver = (new Selector())->forLineCoverage($filter);
            $coverage = new CodeCoverage($driver, $filter);
            $coverage->start('nested-dir-setup');
            require_once $fixtureFile;
            $lc = new LineCoverage();
            $lc->covered();
            $coverage->stop();

            self::$nestedDirCoverage = $coverage;
        }
    }

    #[Test]
    public function it_returns_required_top_level_keys(): void
    {
        $formatter = new JsonFormatter();
        $coverage = $this->createCoverage();

        $result = $formatter->format($coverage);

        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('files', $result);
    }

    #[Test]
    public function meta_contains_format_and_version(): void
    {
        $formatter = new JsonFormatter();
        $coverage = $this->createCoverage();

        $result = $formatter->format($coverage);
        $meta = $result['meta'];

        $this->assertEquals('phpunit-json-coverage', $meta['format']);
        $this->assertEquals(JsonReporter::VERSION, $meta['version']);
        $this->assertNotEmpty($meta['timestamp']);
        $this->assertEquals('phpunit-json-coverage-report', $meta['generator']['name']);
    }

    #[Test]
    public function meta_phpunit_version_at_correct_path(): void
    {
        $formatter = new JsonFormatter();
        $coverage = $this->createCoverage();

        $result = $formatter->format($coverage);

        $this->assertArrayHasKey('phpunit', $result['meta']);
        $this->assertArrayHasKey('version', $result['meta']['phpunit']);
        $this->assertArrayHasKey('coverageLibrary', $result['meta']['phpunit']);
        $this->assertArrayNotHasKey('meta', $result['meta']);
    }

    #[Test]
    public function summary_contains_all_five_metrics(): void
    {
        $formatter = new JsonFormatter();
        $coverage = $this->createCoverage();

        $result = $formatter->format($coverage);

        foreach (['lines', 'branches', 'paths', 'functions', 'classes'] as $metric) {
            $this->assertArrayHasKey($metric, $result['summary'], "summary.$metric missing");
            $this->assertArrayHasKey('total', $result['summary'][$metric]);
            $this->assertArrayHasKey('covered', $result['summary'][$metric]);
            $this->assertArrayHasKey('percentage', $result['summary'][$metric]);
        }
    }

    #[Test]
    public function it_filters_requested_metrics(): void
    {
        $formatter = new JsonFormatter();
        $coverage = $this->createCoverage();

        $result = $formatter->format($coverage, ['lines', 'classes']);

        $this->assertArrayHasKey('lines', $result['summary']);
        $this->assertArrayHasKey('classes', $result['summary']);
        $this->assertArrayNotHasKey('branches', $result['summary']);
        $this->assertArrayNotHasKey('paths', $result['summary']);
        $this->assertArrayNotHasKey('functions', $result['summary']);
    }

    #[Test]
    public function files_contain_path_and_metrics(): void
    {
        $formatter = new JsonFormatter();
        $coverage = $this->createCoverage();

        $result = $formatter->format($coverage);

        $this->assertNotEmpty($result['files']);
        $firstFile = reset($result['files']);
        $this->assertArrayHasKey('path', $firstFile);
        $this->assertArrayHasKey('lines', $firstFile);
    }

    #[Test]
    public function it_uses_custom_extractors(): void
    {
        $formatter = new JsonFormatter([
            'lines' => new LineExtractor(),
        ]);
        $coverage = $this->createCoverage();

        $result = $formatter->format($coverage);

        $this->assertArrayHasKey('lines', $result['summary']);
        $this->assertArrayNotHasKey('branches', $result['summary']);
        $this->assertArrayNotHasKey('classes', $result['summary']);
    }

    #[Test]
    public function empty_extractors_produce_empty_summary(): void
    {
        $formatter = new JsonFormatter([]);
        $coverage = $this->createCoverage();

        $result = $formatter->format($coverage);

        $this->assertEmpty($result['summary']);
    }

    #[Test]
    public function empty_coverage_produces_empty_files(): void
    {
        $formatter = new JsonFormatter();
        $filter = new Filter();
        $driver = (new Selector())->forLineCoverage($filter);
        $coverage = new CodeCoverage($driver, $filter);

        $result = $formatter->format($coverage);

        $this->assertEmpty($result['files']);
    }

    #[Test]
    public function each_file_entry_has_details_key(): void
    {
        $formatter = new JsonFormatter();
        $coverage = $this->createCoverage();

        $result = $formatter->format($coverage);

        foreach ($result['files'] as $path => $fileData) {
            $this->assertArrayHasKey('details', $fileData['lines'], "File $path lines should have details");
        }
    }

    #[Test]
    public function files_section_uses_path_as_key(): void
    {
        $formatter = new JsonFormatter();
        $coverage = $this->createCoverage();

        $result = $formatter->format($coverage);

        foreach ($result['files'] as $path => $fileData) {
            $this->assertEquals($path, $fileData['path']);
        }
    }

    #[Test]
    public function it_processes_nested_directory_structure(): void
    {
        // $nestedDirCoverage was built in setUpBeforeClass() with files from tests/fixtures/
        // AND src/, so its report root is the project root and contains nested Directory nodes.
        // Calling format() here (with PHPUnit's coverage driver active) exercises the
        // elseif ($node instanceof Directory) branch in collectFileNodes() (lines 158-159).
        $formatter = new JsonFormatter(['lines' => new LineExtractor()]);
        $result = $formatter->format(self::$nestedDirCoverage);

        $this->assertArrayHasKey('files', $result);
        $this->assertNotEmpty($result['files']);
    }

    private function createCoverage(): CodeCoverage
    {
        $filter = new Filter();
        $fixtureFile = dirname(__DIR__, 2) . '/fixtures/LineCoverage.php';
        $filter->includeFile($fixtureFile);

        $driver = (new Selector())->forLineCoverage($filter);
        $coverage = new CodeCoverage($driver, $filter);

        $coverage->start('formatter-feature-test');
        require_once $fixtureFile;
        $lc = new LineCoverage();
        $lc->covered();
        $coverage->stop();

        return $coverage;
    }
}
