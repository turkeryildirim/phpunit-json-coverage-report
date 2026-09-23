# Version Compatibility Test Results

## Test Date: 2026-09-23

## Summary

✅ **All tested versions are compatible!**

The reporter uses the PHPUnit 10+ Extension API and Event System and has been validated against all supported version combinations.

## Tested Combinations

| PHPUnit Version | php-code-coverage Version | PHP Versions | Status | Tests Run |
|----------------|---------------------------|--------------|--------|-----------|
| 10.5.65 | 10.1.16 | 8.1, 8.2, 8.3, 8.4 | ✅ PASS | 99 tests, 632 assertions |
| 11.5.56 | 11.0.12 | 8.2, 8.3, 8.4, 8.5 | ✅ PASS | 99 tests, 632 assertions |
| 12.5.35 | 12.5.7 | 8.3, 8.4, 8.5 | ✅ PASS | 99 tests, 632 assertions |
| 13.0.6 | 13.0.2 | 8.4, 8.5 | ✅ PASS | 99 tests, 632 assertions |
| 13.3.4 | 14.3.3 | 8.4, 8.5 | ✅ PASS | 99 tests, 632 assertions |

> PHPUnit 13.0.x requires php-code-coverage 13.x; PHPUnit 13.1 and later require php-code-coverage 14.x.
>
> All combinations were run with the full test suite (Xdebug) in clean `php:<version>-cli` containers, mirroring the CI matrix.

## Test Suites

The test suite is organized into three layers:

| Suite | Count | Purpose |
|-------|-------|---------|
| Unit | 25 tests | Component isolation — constructor behavior, calculator edge cases, mock wiring |
| Feature | 49 tests | Extractor correctness — real `CodeCoverage` data generated from fixture files |
| Integration | 25 tests | End-to-end pipeline — full JSON report generation and cross-format validation |

## Notes

- **PHPUnit 10-13 support**: The package fully supports the latest stable PHPUnit releases, including php-code-coverage 14 (PHPUnit 13.1+).
- **Modern Extension API**: The reporter integrates with the PHPUnit 10+ Event System via `JsonReporterExtension`.
- **Metrics Filtering**: Support for selective metric reporting (`lines`, `branches`, `paths`, `functions`, `classes`) was added in v1.0.0.
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
- ✅ PHPUnit 13.0.x + php-code-coverage 13.x
- ✅ PHPUnit 13.1+ + php-code-coverage 14.x
