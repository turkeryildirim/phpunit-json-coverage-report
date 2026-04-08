# PHPUnit JSON Coverage Report

Comprehensive JSON coverage reporter for PHPUnit. Reports all 5 coverage metrics: **Line**, **Branch**, **Path**, **Function**, and **Class**.

## Why this package exists

`PHPUnit` provides code coverage data via `php-code-coverage`, but it does not offer a native JSON output format.

Existing formats such as HTML, Clover XML, or Cobertura XML are designed for human reading or specific CI tools. They are not convenient for programmatic consumption.

This package fills that gap by exposing coverage data as structured JSON.

---

## Background

There has been prior discussion about adding JSON output support directly to php-code-coverage:

https://github.com/sebastianbergmann/php-code-coverage/issues/829

As of now, JSON output is not available natively. This package provides an external solution.

## How it works

This library is implemented as a PHPUnit extension.  
It accesses the raw coverage data during runtime and serializes it into JSON.

- No XML parsing
- No post-processing required
- Direct access to in-memory coverage data
---

## Use Cases

- CI pipelines (custom coverage checks)
- Programmatic analysis (scripts, tooling)
- Frontend dashboards
- Integration with other systems


## Supported Versions

| PHPUnit | php-code-coverage | Status |
|---------|-------------------|--------|
| 10.x | 10.x | ✅ Tested |
| 11.x | 11.x | ✅ Tested |
| 12.x | 12.x | ✅ Tested |
| 13.x | 13.x | ✅ Tested |

> See [Version Compatibility](docs/VERSION_COMPATIBILITY.md) for detailed test results.

## Installation

```bash
composer require --dev turkeryildirim/phpunit-json-coverage-report
```

## Configuration

Add the reporter to your `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <!-- ... other config ... -->

    <extensions>
        <bootstrap class="Turker\PHPUnitCoverageReporter\JsonReporterExtension">
            <!-- Optional: Custom output path (default: coverage.json) -->
            <parameter name="outputFile" value="coverage.json"/>
            <!-- Optional: Filter metrics (default: all) -->
            <!-- Available: lines, branches, paths, functions, classes -->
            <parameter name="metrics" value="lines,branches,functions,classes"/>
        </bootstrap>
    </extensions>
</phpunit>
```

Run tests with coverage:

```bash
vendor/bin/phpunit
```

A `coverage.json` file will be generated in your project root.

## Documentation

- [Extended README](docs/README.md) - Detailed usage, output format, and all metrics explained
- [Contributing Guide](docs/CONTRIBUTING.md) - For developers who want to fork and extend
- [Version Compatibility](docs/VERSION_COMPATIBILITY.md) - Tested version combinations
- [Example Output](examples/coverage-sample.json) - Sample JSON output

## License

MIT License - see [LICENSE](LICENSE) for details.
