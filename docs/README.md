# PHPUnit JSON Coverage Report - Extended Documentation

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Usage](#usage)
5. [Output Format](#output-format)
6. [Coverage Metrics](#coverage-metrics)
7. [Requirements](#requirements)
8. [Programmatic Usage](#programmatic-usage)
9. [CI/CD Integration](#cicd-integration)

## Overview

This package generates a comprehensive JSON coverage report for PHPUnit, covering all 5 code coverage metrics:

1. **Line Coverage** - Whether each executable line was executed
2. **Branch Coverage** - Whether each boolean expression evaluated to both true and false
3. **Path Coverage** - Whether each possible execution path was followed
4. **Function Coverage** - Whether each function/method was invoked (all lines must be covered)
5. **Class/Trait Coverage** - Whether each class/trait has all methods covered

## Installation

```bash
composer require --dev turkeryildirim/phpunit-json-coverage-report
```

## Configuration

### Basic Configuration

Add to your `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/13.0/phpunit.xsd">
    <testsuites>
        <testsuite name="default">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </source>

    <extensions>
        <bootstrap class="Turker\PHPUnitCoverageReporter\JsonReporterExtension"/>
    </extensions>
</phpunit>
```

### Full Configuration Options

```xml
<extensions>
    <bootstrap class="Turker\PHPUnitCoverageReporter\JsonReporterExtension">
        <!-- Path where the report will be written (default: coverage.json) -->
        <parameter name="outputFile" value="build/coverage.json"/>

        <!-- Comma-separated list of metrics to include (default: all) -->
        <!-- Available values: lines, branches, paths, functions, classes -->
        <parameter name="metrics" value="lines,functions,classes"/>
    </bootstrap>
</extensions>
```

## Usage

```bash
# Run with coverage (Xdebug or PCOV required)
XDEBUG_MODE=coverage vendor/bin/phpunit

# The extension automatically generates coverage.json
```

## Output Format

### Structure

```json
{
    "meta": { ... },
    "summary": { ... },
    "files": { ... }
}
```

### Meta Section

```json
{
    "meta": {
        "format": "phpunit-json-coverage",
        "version": "1.0.0",
        "timestamp": "2026-04-05T12:00:00+00:00",
        "generator": {
            "name": "phpunit-json-coverage-report",
            "version": "1.0.0"
        },
        "phpunit": {
            "version": "13.0.6",
            "coverageLibrary": "13.0.2"
        }
    }
}
```

### Summary Section

```json
{
    "summary": {
        "lines": { "total": 150, "covered": 120, "percentage": 80.0 },
        "branches": { "total": 40, "covered": 28, "percentage": 70.0 },
        "paths": { "total": 15, "covered": 10, "percentage": 66.67 },
        "functions": { "total": 20, "covered": 16, "percentage": 80.0 },
        "classes": { "total": 5, "covered": 4, "percentage": 80.0 }
    }
}
```

### Files Section

Each file contains all 5 metrics with detailed breakdown:

```json
{
    "files": {
        "src/Calculator.php": {
            "path": "src/Calculator.php",
            "lines": { "total": 30, "covered": 28, "percentage": 93.33, "details": { ... } },
            "branches": { "total": 4, "covered": 3, "percentage": 75.0, "details": [ ... ] },
            "paths": { "total": 2, "covered": 1, "percentage": 50.0, "details": [ ... ] },
            "functions": { "total": 3, "covered": 2, "percentage": 66.67, "details": [ ... ] },
            "classes": { "total": 1, "covered": 0, "percentage": 0.0, "details": [ ... ] }
        }
    }
}
```

## Coverage Metrics

### Line Coverage

Measures whether each executable line was executed during the test suite.

```json
{
    "lines": {
        "total": 100,
        "covered": 85,
        "percentage": 85.0,
        "details": {
            "10": { "hits": 5 },
            "11": { "hits": 0 }
        }
    }
}
```

### Branch Coverage

Measures whether boolean expressions in control structures evaluated to both true and false. Requires Xdebug 3.0+ or PCOV with branch coverage enabled.

```json
{
    "branches": {
        "total": 20,
        "covered": 15,
        "percentage": 75.0,
        "details": [
            { "id": 0, "line": 15, "type": "if", "covered": true },
            { "id": 1, "line": 20, "type": "if", "covered": false }
        ]
    }
}
```

### Path Coverage

Measures whether each unique execution path through a function was followed. Requires Xdebug 3.0+.

```json
{
    "paths": {
        "total": 5,
        "covered": 3,
        "percentage": 60.0,
        "details": [
            { "id": 0, "covered": true },
            { "id": 1, "covered": false }
        ]
    }
}
```

### Function Coverage

A function is covered only when **all** its executable lines are covered.

```json
{
    "functions": {
        "total": 10,
        "covered": 8,
        "percentage": 80.0,
        "details": [
            {
                "name": "calculate",
                "signature": "int $a, int $b",
                "startLine": 10,
                "endLine": 25,
                "executableLines": 10,
                "executedLines": 10,
                "coverage": 100.0,
                "covered": true
            }
        ]
    }
}
```

### Class/Trait Coverage

A class is covered only when **all** its methods are covered. Only classes and traits that have at least one method with executable lines are counted — empty classes, marker interfaces, and classes with no executable methods are excluded from `total`.

```json
{
    "classes": {
        "total": 5,
        "covered": 4,
        "percentage": 80.0,
        "details": [
            {
                "name": "Calculator",
                "type": "class",
                "namespace": "App\\Math",
                "methods": [ ... ],
                "covered": true
            }
        ]
    }
}
```

> **Note**: The `details` array includes all discovered classes/traits (including empty ones for visibility), but `total` and `covered` only count those with executable content. This matches the `numberOfClasses()` / `numberOfTraits()` behavior of `php-code-coverage` and ensures `summary.classes.total` equals the sum of all per-file `classes.total` values.

## Requirements

- **PHP**: 8.1 or higher
- **PHPUnit**: 10.0, 11.0, 12.0, or 13.0
- **php-code-coverage**: Matching PHPUnit version
- **Driver**: Xdebug 3.0+ or PCOV (for branch/path coverage)

## Programmatic Usage

```php
<?php

use Turker\PHPUnitCoverageReporter\JsonReporter;
use SebastianBergmann\CodeCoverage\CodeCoverage;

// After collecting coverage data
$reporter = new JsonReporter();

// Optional: Provide a list of metrics to include
$metrics = ['lines', 'classes'];

try {
    $reporter->process($coverage, 'coverage.json', $metrics);
} catch (\RuntimeException $e) {
    echo "Error generating report: " . $e->getMessage();
}
```

## CI/CD Integration

### GitHub Actions

```yaml
- name: Run tests with coverage
  run: vendor/bin/phpunit

- name: Upload coverage JSON
  uses: actions/upload-artifact@v4
  with:
    name: coverage-report
    path: coverage.json
```

### GitLab CI

```yaml
test:
  script:
    - vendor/bin/phpunit
  artifacts:
    paths:
      - coverage.json
```
