<?php

namespace Jasny\HttpMessage\ServerRequest;

use PHPUnit_Framework_TestCase;
use Jasny\TestHelper;

use Jasny\HttpMessage\ServerRequest;

/**
 * @covers Jasny\HttpMessage\ServerRequest\ServerParams
 */
class ServerParamsTest extends PHPUnit_Framework_TestCase
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
    

    public function testGetServerParamsDefault()
    {
        $this->assertSame([], $this->baseRequest->getServerParams());
    }

    public function testWithServerParams()
    {
        $params = ['SERVER_SOFTWARE' => 'Foo 1.0', 'COLOR' => 'red', 'SCRIPT_FILENAME' => 'qux.php'];
        
        $request = $this->baseRequest->withServerParams($params);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals($params, $request->getServerParams());
    }

    /**
     * @depends testWithServerParams
     */
    public function testWithServerParamsReset()
    {
        $request = $this->baseRequest->withMethod('POST')->withServerParams([]);
        
        $this->assertEquals('', $request->getMethod());
    }

    public function testWithServerParamsTurnStale()
    {
        $this->setPrivateProperty($this->baseRequest, 'isStale', false);
        
        $request = $this->baseRequest->withServerParams([]);
        
        $this->assertTrue($this->baseRequest->isStale());
        $this->assertFalse($request->isStale());
    }
}
