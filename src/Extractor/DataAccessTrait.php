<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Extractor;

/**
 * Provides unified access to coverage data properties.
 *
 * This trait allows extracting data from either objects or associative arrays,
 * abstracting version-specific differences in php-code-coverage internal structures.
 */
trait DataAccessTrait
{
    /**
     * Retrieve a value from an object property or an array key.
     *
     * @param object|array $data Data container.
     * @param string $key Property or key name.
     * @param mixed $default Default value if key is not found.
     * @return mixed The extracted value.
     */
    protected function getValue(object|array $data, string $key, mixed $default = null): mixed
    {
        if (is_object($data)) {
            return $data->{$key} ?? $default;
        }

        return $data[$key] ?? $default;
    }
}
