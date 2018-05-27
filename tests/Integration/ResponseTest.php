<?php

namespace Jasny\HttpMessage\Tests\Integration;

use Http\Psr7Test\ResponseIntegrationTest;
use Jasny\HttpMessage\Response;

class ResponseTest extends ResponseIntegrationTest
{
    public function createSubject()
    {
        return new Response();
    }
}
