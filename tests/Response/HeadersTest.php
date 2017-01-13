<?php

namespace Jasny\HttpMessage\Response;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Jasny\TestHelper;

use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\Headers;

/**
 * @covers Jasny\HttpMessage\Message\Headers
 * @covers Jasny\HttpMessage\Response\Headers
 */
class HeadersTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    /**
     * @var Response
     */
    protected $baseResponse;
    
    /**
     * @var Headers|MockObject
     */
    protected $headers;
    
    public function setUp()
    {
        $this->baseResponse = new Response();
        
        $this->headers = $this->createMock(Headers::class);
        $this->setPrivateProperty($this->baseResponse, 'headers', $this->headers);
    }
    

    public function testGetDefaultHeaders()
    {
        $response = new Response();
        $this->assertEquals([], $response->getHeaders());
    }
    
    public function testWithHeader()
    {
        $this->headers->expects($this->once())->method('withHeader')->willReturnSelf();
        
        $response = $this->baseResponse->withHeader('Foo', 'Baz');
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
    }

    public function testWithAddedHeader()
    {
        $this->headers->expects($this->once())->method('withAddedHeader')->willReturnSelf();
        
        $response = $this->baseResponse->withAddedHeader('Foo', 'Baz');
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
    }

    public function testWithoutHeader()
    {
        $this->headers->expects($this->once())->method('withoutHeader')->willReturnSelf();
        $this->headers->expects($this->atLeastOnce())->method('hasHeader')->with('Foo')->willReturn(true);
        
        $response = $this->baseResponse->withoutHeader('Foo');
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
    }

    public function testWithoutNonExistantHeader()
    {
        $this->headers->expects($this->never())->method('withoutHeader')->willReturnSelf();
        $this->headers->expects($this->atLeastOnce())->method('hasHeader')->with('Foo')->willReturn(false);
        
        $response = $this->baseResponse->withoutHeader('Foo');
        
        $this->assertSame($this->baseResponse, $response);
    }

    public function testHasHeader()
    {
        $this->headers->expects($this->once())->method('hasHeader')->with('Foo')->willReturn(true);
        
        $this->assertTrue($this->baseResponse->hasHeader('Foo'));
    }

    public function testGetHeader()
    {
        $this->headers->expects($this->once())->method('getHeader')->with('Foo')->willReturn(['Baz', 'Car']);
        
        $this->assertSame(['Baz', 'Car'], $this->baseResponse->getHeader('Foo'));
    }

    public function testGetHeaderLine()
    {
        $this->headers->expects($this->once())->method('getHeaderLine')->with('Foo')->will($this->returnValue('Baz'));
        
        $this->assertSame('Baz', $this->baseResponse->getHeaderLine('Foo'));
    }
}
