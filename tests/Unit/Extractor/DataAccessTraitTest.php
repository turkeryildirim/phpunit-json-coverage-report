<?php

declare(strict_types=1);

namespace Turker\PHPUnitCoverageReporter\Tests\Unit\Extractor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Turker\PHPUnitCoverageReporter\Extractor\DataAccessTrait;

/**
 * Unit tests for the DataAccessTrait trait.
 */
class DataAccessTraitTest extends TestCase
{
    /**
     * Test extracting values from objects and arrays using the trait.
     */
    #[Test]
    public function get_value_from_various_containers(): void
    {
        $dummy = new class {
            use DataAccessTrait;
            public function callGetValue($data, $key, $default = null) {
                return $this->getValue($data, $key, $default);
            }
        };

        // Test with array
        $arrayData = ['key' => 'value'];
        $this->assertEquals('value', $dummy->callGetValue($arrayData, 'key'));
        $this->assertEquals('default', $dummy->callGetValue($arrayData, 'missing', 'default'));

        // Test with object
        $objData = (object) ['key' => 'value'];
        $this->assertEquals('value', $dummy->callGetValue($objData, 'key'));
        $this->assertEquals('default', $dummy->callGetValue($objData, 'missing', 'default'));
    }
}
