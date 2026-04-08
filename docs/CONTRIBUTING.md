# Contributing Guide

## Development Setup

```bash
git clone https://github.com/turkeryildirim/phpunit-json-coverage-report.git
cd phpunit-json-coverage-report
composer install
```

## Running Tests

```bash
# Full suite
vendor/bin/phpunit

# Individual suites
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite feature
vendor/bin/phpunit --testsuite integration

# Without coverage (faster)
vendor/bin/phpunit --no-coverage
```

## Project Structure

```
src/
├── JsonReporter.php              # Entry point — writes coverage to JSON file
├── JsonReporterExtension.php     # PHPUnit extension bootstrap (reads phpunit.xml params)
├── JsonReporterSubscriber.php    # ExecutionFinished subscriber — triggers report generation
├── Formatter/
│   └── JsonFormatter.php         # Builds the full JSON structure from report nodes
├── Extractor/
│   ├── CoverageExtractorInterface.php  # Contract: extractSummary() + extractFileData()
│   ├── DataAccessTrait.php             # Abstracts object/array access across lib versions
│   ├── LineExtractor.php         # Line coverage metric
│   ├── BranchExtractor.php       # Branch coverage metric
│   ├── PathExtractor.php         # Path coverage metric
│   ├── FunctionExtractor.php     # Function/method coverage metric
│   └── ClassExtractor.php        # Class/trait coverage metric
└── Util/
    └── CoverageCalculator.php    # Percentage calculation helper

tests/
├── Unit/                         # Component isolation (25 tests)
│   ├── Extractor/
│   ├── Formatter/
│   └── Util/
├── Feature/                      # Extractor correctness with real coverage data (49 tests)
│   ├── FeatureTestCase.php        # Generates fixture coverage once in setUpBeforeClass()
│   └── Extractor/
├── Integration/                  # End-to-end report generation (25 tests)
│   ├── IntegrationTestCase.php    # Provides shared CodeCoverage objects
│   ├── FullReportTest.php
│   ├── ReporterTest.php
│   └── ErrorHandlingTest.php
├── fixtures/                     # PHP files exercised during feature/integration tests
└── bootstrap.php
```

## Architecture

### Data Flow

```
PHPUnit Test Runner
       │
       ▼
CodeCoverage::getReport()
       │
       ▼
Directory Node (root)
  └── File Nodes (per source file)
        ├── numberOfExecutableLines() / numberOfExecutedLines()
        ├── numberOfExecutableBranches() / numberOfExecutedBranches()
        ├── numberOfExecutablePaths() / numberOfExecutedPaths()
        ├── numberOfFunctions() / numberOfTestedFunctions()
        ├── numberOfClasses() / numberOfTestedClasses()
        ├── numberOfTraits() / numberOfTestedTraits()
        ├── lineCoverageData()
        ├── functions()
        ├── classes()
        └── traits()
       │
       ▼
JsonFormatter::format()
  ├── formatMeta()           → meta section
  ├── formatSummary()        → summary section (extractSummary per extractor)
  └── formatFiles()          → files section (extractFileData per file per extractor)
       │
       ▼
JsonReporter::process() → JSON file output
```

### Key API: `SebastianBergmann\CodeCoverage\Node\File`

| Method | Returns | Notes |
|--------|---------|-------|
| `numberOfExecutableLines()` | `int` | All non-null lines in `lineCoverageData()` |
| `numberOfExecutedLines()` | `int` | Lines with at least one test hit |
| `numberOfExecutableBranches()` | `int` | Requires Xdebug branch mode |
| `numberOfExecutedBranches()` | `int` | |
| `numberOfExecutablePaths()` | `int` | Requires Xdebug path mode |
| `numberOfExecutedPaths()` | `int` | |
| `numberOfFunctions()` | `int` | Functions with `executableLines > 0` |
| `numberOfTestedFunctions()` | `int` | Functions where `coverage === 100` |
| `numberOfClasses()` | `int` | Classes where ≥1 method has `executableLines > 0` |
| `numberOfTestedClasses()` | `int` | Combines tested classes **and** tested traits internally |
| `numberOfTraits()` | `int` | Traits where ≥1 method has `executableLines > 0` |
| `numberOfTestedTraits()` | `int` | Always 0 (counted inside `numberOfTestedClasses`) |

> **Important**: `numberOfClasses()` excludes classes with no executable methods (e.g. marker interfaces, empty classes). This is the canonical count used for coverage reporting. Do not use `count(classes())` for totals — it includes all classes regardless of executability and will produce inconsistent summary vs per-file numbers.

### Extractor Pattern

Each extractor implements `CoverageExtractorInterface`:

```php
interface CoverageExtractorInterface
{
    public function extractSummary(AbstractNode $node): array;
    // Returns: ['total' => int, 'covered' => int, 'percentage' => float]

    public function extractFileData(File $file): array;
    // Returns: ['total' => int, 'covered' => int, 'percentage' => float, 'details' => array]
}
```

**Consistency rule**: `extractSummary()` uses the same counting logic as the php-code-coverage `numberOfX()` API. `extractFileData()` must use the same methods (`$file->numberOfClasses()`, etc.) so that the sum of all per-file totals equals the summary total.

### DataAccessTrait

In older php-code-coverage versions, class/function data was returned as plain arrays. In newer versions, it is returned as typed value objects. `DataAccessTrait::getValue($container, 'key', $default)` abstracts this difference:

```php
use Turker\PHPUnitCoverageReporter\Extractor\DataAccessTrait;

class MyExtractor implements CoverageExtractorInterface {
    use DataAccessTrait;

    public function extractFileData(File $file): array
    {
        foreach ($file->classes() as $name => $class) {
            $lines = $this->getValue($class, 'executableLines', 0);
        }
    }
}
```

## Adding a New Extractor

1. Create `src/Extractor/YourExtractor.php` implementing `CoverageExtractorInterface`
2. Register it in `JsonFormatter::__construct()` in the `$this->extractors` array with a unique key
3. Add Feature tests in `tests/Feature/Extractor/YourExtractorTest.php` — include an `it_matches_php_code_coverage_api_for_summary()` test
4. The formatter automatically includes the new metric in both summary and per-file output

## Testing Philosophy

### Three Test Layers

**Unit tests** (`tests/Unit/`): Test individual classes in isolation using stubs/fakes. Do not generate real coverage data. Use `createStub()` (not `createMock()`) when no expectations are set.

**Feature tests** (`tests/Feature/`): Generate real `CodeCoverage` data from the fixture PHP files in `setUpBeforeClass()`, before PHPUnit's own coverage window. Test each extractor against this real data. Key assertions:

- `extractSummary()` values must equal the php-code-coverage node API directly:
  ```php
  $this->assertEquals($report->numberOfExecutableLines(), $summary['total']);
  $this->assertEquals($report->numberOfExecutedLines(),   $summary['covered']);
  ```
- `extractFileData()` values must equal the file node API:
  ```php
  $this->assertEquals($fileNode->numberOfExecutableLines(), $fileData['total']);
  $this->assertEquals($fileNode->numberOfExecutedLines(),   $fileData['covered']);
  ```
- For `LineExtractor`, `count($fileData['details'])` must equal `$fileData['total']`

**Integration tests** (`tests/Integration/`): Generate full JSON reports and verify end-to-end correctness:

- **Mathematical consistency**: sum of `files[*].metric.total` must equal `summary.metric.total`
- **API consistency**: summary values must match `$coverage->getReport()->numberOfX()` directly
- **Cross-format validation**: line counts must match Clover XML output from the same `CodeCoverage` object:
  ```php
  $cloverXml = (new \SebastianBergmann\CodeCoverage\Report\Clover())->process($coverage);
  $xml = simplexml_load_string($cloverXml);
  $this->assertEquals((int) $xml->project->metrics['statements'],        $data['summary']['lines']['total']);
  $this->assertEquals((int) $xml->project->metrics['coveredstatements'], $data['summary']['lines']['covered']);
  ```

## Version Compatibility Testing

```bash
# Switch to a different PHPUnit version
composer require phpunit/phpunit:^12.0 phpunit/php-code-coverage:^12.0 --with-all-dependencies
vendor/bin/phpunit --no-coverage
```

## Coding Standards

- PHP 8.1+ features allowed
- `declare(strict_types=1)` required in all files
- PSR-4 autoloading
- Type declarations for all parameters and return types
- Use `createStub()` for test doubles with no call expectations; use `createMock()` only when asserting method calls
