<?php

namespace Jasny\HttpMessage\Tests\Integration;

use Http\Psr7Test\StreamIntegrationTest;
use Psr\Http\Message\StreamInterface;
use Jasny\HttpMessage\Stream;

class StreamTest extends StreamIntegrationTest
{
    public function createStream($data)
    {
        if ($data instanceof StreamInterface) {
            return $data;
        }

        return new Stream($data);
    }
}
