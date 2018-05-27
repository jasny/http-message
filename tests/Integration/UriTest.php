<?php

namespace Jasny\HttpMessage\Tests\Integration;

use Http\Psr7Test\UriIntegrationTest;
use Jasny\HttpMessage\Uri;

class UriTest extends UriIntegrationTest
{
    public function createUri($uri)
    {
        return new Uri($uri);
    }
}
