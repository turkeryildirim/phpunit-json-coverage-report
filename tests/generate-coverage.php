<?php

/**
 * Generate a coverage report from fixture files.
 *
 * Usage: php tests/generate-coverage.php [output-path]
 *
 * Default output: tests/output/sample-coverage.json
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$outputFile = $argv[1] ?? $projectRoot . '/tests/output/sample-coverage.json';

require_once $projectRoot . '/vendor/autoload.php';

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use Turker\PHPUnitCoverageReporter\JsonReporter;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\BlogPost;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\Circle;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\CollectionProcessor;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\Config;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\Counter;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\DataProcessor;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\EventEmitter;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\FullyCovered;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\FunctionCoverage;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\HttpMethod;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\HttpStatus;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\LineCoverage;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\NestedExceptionHandling;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\NumberGenerator;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\PartiallyCovered;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\PhpFeatures;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\Point;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\Rectangle;
use Turker\PHPUnitCoverageReporter\Tests\fixtures\TemplateEngine;
use function Turker\PHPUnitCoverageReporter\Tests\fixtures\calculateDiscount;
use function Turker\PHPUnitCoverageReporter\Tests\fixtures\formatCurrency;
use function Turker\PHPUnitCoverageReporter\Tests\fixtures\isEven;

$fixturesDir = $projectRoot . '/tests/fixtures';
$outputDir = dirname($outputFile);

if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $outputDir));
    }
}

// 1. Create filter with fixture files
$filter = new Filter();
foreach (glob($fixturesDir . '/*.php') as $file) {
    $filter->includeFile($file);
}

// 2. Create CodeCoverage
$driver = (new Selector())->forLineCoverage($filter);
$coverage = new CodeCoverage($driver, $filter);

// 3. Collect coverage - require files AND exercise code paths
$coverage->start('generate-coverage');

foreach (glob($fixturesDir . '/*.php') as $file) {
    require_once $file;
}

// Exercise Turker\PHPUnitCoverageReporter\Tests\fixtures\LineCoverage
$lc = new LineCoverage();
$lc->covered();
$lc->partiallyCovered();

// Exercise Turker\PHPUnitCoverageReporter\Tests\fixtures\FunctionCoverage
$fc = new FunctionCoverage();
$fc->covered();
$fc->alsoCovered();
$fc->partiallyCovered();

// Exercise ClassCoverage
$full = new FullyCovered();
$full->method1();
$full->method2();
$full->method3();

$partial = new PartiallyCovered();
$partial->covered();
$partial->alsoCovered();

// Exercise PhpFeatures
$pf = new PhpFeatures(1, 'test');
$pf->classify(200);
$pf->classify(404);
$pf->classify(500);
$pf->safeDivide(10, 2);
$pf->safeDivide(10, 0);
$pf->createPoint(1.0, 2.0);
$pf->getTransformer();
$pf->getMultiplier(2);
$pf->createLogger();
$pf->getLength('hello');
$pf->getLength(null);
$pf->sum(1, 2, 3);
$pf->getCoordinates();
$pf->countItems(new ArrayObject([1, 2, 3]));

$point = new Point(1.0, 2.0);
$point->distanceTo(new Point(3.0, 4.0));

$status = HttpStatus::OK;
$status->isSuccessful();

$blog = new BlogPost('title', 'content');
$blog->setCreatedAt(new DateTimeImmutable());
$blog->getCreatedAt();

$neh = new NestedExceptionHandling();
$neh->process('valid');
$neh->process(123);

// Exercise PhpFeaturesExtended
$circle = new Circle(5.0);
$circle->area();
$circle->describe();

$rect = new Rectangle(3.0, 4.0);
$rect->area();
$rect->getLogEntry();
$rect->getLogLevel();

Counter::reset();
Counter::increment();
Counter::getCount();

$gen = new NumberGenerator();
foreach ($gen->fibonacci(10) as $n) { /* iterate */ }
foreach ($gen->range(1, 5) as $n) { /* iterate */ }

$config = new Config('test');
$config->set('key', 'value');
$config->get('key');

$emitter = new EventEmitter();
$emitter->on('test', fn($d) => $d);
$emitter->emit('test', 'data');
$emitter->createGreeter('Hi')('World');

$template = new TemplateEngine();
$template->render('User', 30);

HttpMethod::isValid('GET');
HttpMethod::isValid('PATCH');

$processor = new DataProcessor();
$processor->parse('{"a":1}');
$processor->parse('bad');
$processor->convert([1, 2]);
$processor->convert('x');

$cp = new CollectionProcessor();
$cp->countTraversable(new ArrayObject([1, 2]));

calculateDiscount(100.0, 15.0);
formatCurrency(42.50);
isEven(4);
isEven(7);

$coverage->stop();

// 4. Generate JSON report
$reporter = new JsonReporter();
$reporter->process($coverage, $outputFile);

echo 'OK' . PHP_EOL;
