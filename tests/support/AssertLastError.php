<?php

namespace Jasny\HttpMessage\Tests;

/**
 * Assert the last error
 */
trait AssertLastError
{
    /**
     * Assert the last error
     * 
     * @param int    $type     Expected error type, E_* constant
     * @param string $message  Expected error message
     */
    protected function assertLastError($type, $message = null)
    {
        $expected = compact('type') + (isset($message) ? compact('message') : []);
        $this->assertArraySubset($expected, error_get_last());
    }
}