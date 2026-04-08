# CLAUDE.md — phpunit-json-coverage-report

## Project Purpose

PHPUnit extension that generates a comprehensive JSON coverage report covering all 5 metrics: **line, branch, path, function, class**. Supports PHPUnit 10–13 and the matching `php-code-coverage` versions.

## Commands

```bash
# Full test suite
vendor/bin/phpunit

# Individual suites
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite feature
vendor/bin/phpunit --testsuite integration

# Without coverage collection (faster feedback loop)
vendor/bin/phpunit --no-coverage
```

## Source Layout

```
src/
├── JsonReporter.php              # process(CodeCoverage, outputFile, metrics[]) → writes JSON
├── JsonReporterExtension.php     # PHPUnit extension — reads phpunit.xml params, registers subscriber
├── JsonReporterSubscriber.php    # ExecutionFinished → triggers JsonReporter
├── Formatter/JsonFormatter.php   # format() → ['meta', 'summary', 'files']
├── Extractor/
│   ├── CoverageExtractorInterface.php
│   ├── DataAccessTrait.php       # getValue($obj_or_array, 'key', $default)
│   ├── LineExtractor.php
│   ├── BranchExtractor.php
│   ├── PathExtractor.php
│   ├── FunctionExtractor.php
│   └── ClassExtractor.php
└── Util/CoverageCalculator.php   # percentage(covered, total): float
```

## Test Layout

```
tests/
├── Unit/           # 25 tests  — stubs/fakes, no real coverage data
├── Feature/        # 49 tests  — real CodeCoverage from fixture files
├── Integration/    # 25 tests  — full report generation end-to-end
├── fixtures/       # PHP files exercised during tests
└── bootstrap.php
```

## Critical Design Decisions

### 1. Class Counting — `numberOfClasses()` vs `count(classes())`

**Do not use `count($file->classes())`** for the `total` field in `ClassExtractor::extractFileData()`.

`File::numberOfClasses()` only counts classes where **at least one method has `executableLines > 0`**. `count($file->classes())` counts all classes including empty ones (e.g. `EmptyClass`, marker interfaces). Using the raw count causes `summary.classes.total` to be smaller than the sum of per-file totals — a visible inconsistency in the JSON output.

Always use:
```php
$total   = $file->numberOfClasses() + $file->numberOfTraits();
$covered = $file->numberOfTestedClasses() + $file->numberOfTestedTraits();
```

Note: `php-code-coverage` internally increments `numTestedClasses` for **both** tested classes and tested traits. `numberOfTestedTraits()` always returns 0.

### 2. Extractor Consistency Rule

Every extractor's `extractSummary()` and `extractFileData()` must use the same php-code-coverage API so that:

```
summary.metric.total == sum of files[*].metric.total
summary.metric.covered == sum of files[*].metric.covered
```

This is enforced by `FullReportTest::summary_equals_sum_of_all_file_metrics()`.

### 3. Mock vs Stub

Use `createStub()` when a test double needs no call expectations. Use `createMock()` only when asserting that a specific method is called. PHPUnit 13 emits a Notice for mocks with no expectations configured.

## Correctness Testing Strategy

### Feature Tests — Direct API Comparison

Each extractor test compares the extractor's output against the php-code-coverage API directly:

```php
// Summary
$this->assertEquals($report->numberOfExecutableLines(), $summary['total']);
$this->assertEquals($report->numberOfExecutedLines(),   $summary['covered']);

// Per-file
$this->assertEquals($fileNode->numberOfExecutableLines(), $fileData['total']);
$this->assertEquals($fileNode->numberOfExecutedLines(),   $fileData['covered']);
```

### Integration Tests — Consistency & Cross-Format

`FullReportTest` contains:

| Test | What it verifies |
|------|-----------------|
| `summary_equals_sum_of_all_file_metrics` | sum of per-file lines/functions/classes == summary |
| `summary_line_counts_match_php_code_coverage_report` | summary values == `getReport()->numberOfX()` directly |
| `summary_line_counts_match_clover_xml` | JSON line counts == Clover XML `statements`/`coveredstatements` |

### Line Details Invariant

`count($fileData['lines']['details'])` must equal `$fileData['lines']['total']` — verified by `LineExtractorTest::details_count_equals_total_lines()`.

## Adding a New Metric

1. Create `src/Extractor/YourExtractor.php` implementing `CoverageExtractorInterface`
2. Use `$file->numberOfX()` (not `count($file->xs())`) for `total` in `extractFileData()`
3. Register in `JsonFormatter::__construct()` `$this->extractors` array
4. Add `tests/Feature/Extractor/YourExtractorTest.php` with an `it_matches_php_code_coverage_api_for_summary()` test
