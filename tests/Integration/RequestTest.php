<?php

namespace Jasny\HttpMessage\Tests\Integration;

use Http\Psr7Test\RequestIntegrationTest;
use Jasny\HttpMessage\Request;

class RequestTest extends RequestIntegrationTest
{
    public function createSubject()
    {
        return new Request('/', 'GET');
    }
}
