<?php

namespace Jasny\HttpMessage\ServerRequest;

use PHPUnit_Framework_TestCase;
use Jasny\TestHelper;

use Jasny\HttpMessage\ServerRequest;

/**
 * @covers Jasny\HttpMessage\ServerRequest\CookieParams
 */
class CookieParamsTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    /**
     * @var ServerRequest
     */
    protected $baseRequest;
    
    public function setUp()
    {
        $this->baseRequest = new ServerRequest();
    }
    

    public function testGetCookieParamsDefault()
    {
        $this->assertSame([], $this->baseRequest->getCookieParams());
    }

    public function testWithCookieParams()
    {
        $request = $this->baseRequest->withCookieParams(['foo' => 'bar', 'color' => 'red']);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame(['foo' => 'bar', 'color' => 'red'], $request->getCookieParams());
    }

    public function testWithCookieParamsTurnStale()
    {
        $refl = new \ReflectionProperty(ServerRequest::class, 'isStale');
        $refl->setAccessible(true);
        $refl->setValue($this->baseRequest, false);
        
        $request = $this->baseRequest->withCookieParams([]);
        
        $this->assertTrue($this->baseRequest->isStale());
        $this->assertFalse($request->isStale());
    }
}
