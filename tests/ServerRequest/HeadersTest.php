<?php

namespace Jasny\HttpMessage\ServerRequest;

use PHPUnit_Framework_TestCase;
use Jasny\TestHelper;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\Headers as HeaderObject;

/**
 * @covers Jasny\HttpMessage\Message\Headers
 * @covers Jasny\HttpMessage\ServerRequest\Headers
 */
class HeadersTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    /**
     * @var ServerRequest
     */
    protected $baseRequest;
    
    /**
     * @var HeadersInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $headers;

    public function setUp()
    {
        $refl = new \ReflectionProperty(ServerRequest::class, 'headers');
        $refl->setAccessible(true);
        
        $this->baseRequest = new ServerRequest();
        $this->headers = $this->createMock(HeaderObject::class);
        $refl->setValue($this->baseRequest, $this->headers);
    }

    /**
     * @internal Tight coupling in `initHeaders()` means `Headers::getHeaders()` must work properly
     */
    public function testDetermineHeaders()
    {
        $request = (new ServerRequest())->withServerParams([
            'CONTENT_TYPE' => 'text/plain',
            'HTTP_FOO' => 'bar',
            'HTTP_ACCEPT_CHARSET' => 'utf-8',
            'HTTPS' => 'yes'
        ]);
        
        $this->assertEquals([
            'Content-Type' => ['text/plain'],
            'Foo' => ['bar'],
            'Accept-Charset' => ['utf-8']
        ], $request->getHeaders());
    }

    public function testWithHeader()
    {
        $this->headers->expects($this->once())
            ->method('withHeader')
            ->with('Foo', 'Baz')
            ->will($this->returnSelf());
        
        $request = $this->baseRequest->withHeader('Foo', 'Baz');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
    }

    public function testWithAddedHeader()
    {
        $this->headers->expects($this->once())
            ->method('withAddedHeader')
            ->with('Foo', 'Baz')
            ->will($this->returnSelf());
        
        $request = $this->baseRequest->withAddedHeader('Foo', 'Baz');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
    }

    public function testWithoutHeader()
    {
        $this->headers->expects($this->once())
            ->method('hasHeader')
            ->with('Foo')
            ->willReturn(true);
        
        $this->headers->expects($this->once())
            ->method('withoutHeader')
            ->with('Foo')
            ->will($this->returnSelf());
        
        $request = $this->baseRequest->withoutHeader('Foo');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
    }

    public function testWithoutHeaderNonExistent()
    {
        $this->headers->expects($this->once())
            ->method('hasHeader')
            ->with('Foo')
            ->willReturn(false);
        
        $this->headers->expects($this->never())->method('withoutHeader');
        
        $request = $this->baseRequest->withoutHeader('Foo');
        
        $this->assertSame($this->baseRequest, $request);
    }

    public function testGetHeaders()
    {
        $this->headers->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['Foo' => ['bar'], 'Content-Type' => ['text/plain']]);
        
        $this->assertEquals(
            ['Foo' => ['bar'], 'Content-Type' => ['text/plain']],
            $this->baseRequest->getHeaders()
        );
    }

    public function testHasHeader()
    {
        $this->headers->expects($this->once())
            ->method('hasHeader')
            ->with('Foo')
            ->willReturn(true);
        
        $this->assertTrue($this->baseRequest->hasHeader('Foo'));
    }

    public function testGetHeader()
    {
        $this->headers->expects($this->once())
            ->method('getHeader')
            ->with('Foo')
            ->willReturn(['Baz']);
        
        $this->assertSame(['Baz'], $this->baseRequest->getHeader('Foo'));
    }

    public function testGetHeaderLine()
    {
        $this->headers->expects($this->once())
            ->method('getHeaderLine')
            ->with('Foo')
            ->willReturn('Baz');
        
        $this->assertSame('Baz', $this->baseRequest->getHeaderLine('Foo'));
    }
}
