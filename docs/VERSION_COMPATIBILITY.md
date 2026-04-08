# Version Compatibility Test Results

## Test Date: 2026-04-07

## Summary

✅ **All tested versions are compatible!**

The reporter uses the PHPUnit 10+ Extension API and Event System and has been validated against all supported version combinations.

## Tested Combinations

| PHPUnit Version | php-code-coverage Version | Status | Tests Run | Result |
|----------------|---------------------------|--------|-----------|---------|
| 10.5.x | 10.1.x | ✅ PASS | 99 tests, 631 assertions | OK |
| 11.5.x | 11.0.x | ✅ PASS | 99 tests, 631 assertions | OK |
| 12.5.x | 12.5.x | ✅ PASS | 99 tests, 631 assertions | OK |
| 13.0.x | 13.0.x | ✅ PASS | 99 tests, 631 assertions | OK |

## Test Suites

The test suite is organized into three layers:

| Suite | Count | Purpose |
|-------|-------|---------|
| Unit | 25 tests | Component isolation — constructor behavior, calculator edge cases, mock wiring |
| Feature | 49 tests | Extractor correctness — real `CodeCoverage` data generated from fixture files |
| Integration | 25 tests | End-to-end pipeline — full JSON report generation and cross-format validation |

## Notes

- **PHPUnit 10-13 support**: The package fully supports the latest stable PHPUnit releases.
- **Modern Extension API**: The reporter integrates with the PHPUnit 10+ Event System via `JsonReporterExtension`.
- **Metrics Filtering**: Support for selective metric reporting (`lines`, `branches`, `paths`, `functions`, `classes`) was added in v1.1.0.
- **Correctness testing**: Feature tests verify extractor output against php-code-coverage's own API (`numberOfExecutableLines()` etc.) to ensure no missing or extra reporting.
- **Cross-format validation**: Integration tests compare JSON line counts against Clover XML output from the same `CodeCoverage` object.

## Test Command

```bash
# Run the full test suite
vendor/bin/phpunit

# Run individual suites
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite feature
vendor/bin/phpunit --testsuite integration

# Test with a specific PHPUnit version
composer require phpunit/phpunit:^11.0 phpunit/php-code-coverage:^11.0 --with-all-dependencies
vendor/bin/phpunit
```

## Conclusion

The phpunit-json-coverage-report package is compatible with:
- ✅ PHPUnit 10.x + php-code-coverage 10.x
- ✅ PHPUnit 11.x + php-code-coverage 11.x
- ✅ PHPUnit 12.x + php-code-coverage 12.x
- ✅ PHPUnit 13.x + php-code-coverage 13.x
