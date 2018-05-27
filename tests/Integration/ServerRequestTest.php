<?php

namespace Jasny\HttpMessage\Tests\Integration;

use Http\Psr7Test\ServerRequestIntegrationTest;
use Jasny\HttpMessage\ServerRequest;

class ServerRequestTest extends ServerRequestIntegrationTest
{
    public function createSubject()
    {
        return new ServerRequest($_SERVER);
    }
}
