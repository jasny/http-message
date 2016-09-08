<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use PHPUnit_Framework_TestCase;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\Stream;

/**
 * @covers Jasny\HttpMessage\ServerRequest
 * @covers Jasny\HttpMessage\ServerRequest\ProtocolVersion
 * @covers Jasny\HttpMessage\ServerRequest\Headers
 * @covers Jasny\HttpMessage\ServerRequest\Body
 * @covers Jasny\HttpMessage\ServerRequest\RequestTarget
 * @covers Jasny\HttpMessage\ServerRequest\Method
 * @covers Jasny\HttpMessage\ServerRequest\Uri
 * @covers Jasny\HttpMessage\ServerRequest\ServerParams
 * @covers Jasny\HttpMessage\ServerRequest\Cookies
 * @covers Jasny\HttpMessage\ServerRequest\QueryParams
 * @covers Jasny\HttpMessage\ServerRequest\UploadedFiles
 * @covers Jasny\HttpMessage\ServerRequest\ParsedBody
 * @covers Jasny\HttpMessage\ServerRequest\Attributes
 */
class ServerRequestTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequest
     */
    protected $baseRequest;
    
    public function setUp()
    {
        $this->baseRequest = new ServerRequest();
    }
    
    
    public function testWithSuperGlobals()
    {
        $request = $this->baseRequest->withSuperGlobals();
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('php://input', $request->getBody()->getMetadata('uri'));
    }
    
    
    public function testDefaultProtocolVersion()
    {
        $this->assertEquals('1.0', $this->baseRequest->getProtocolVersion());
    }
    
    public function testDetermineProtocolVersion()
    {
        $request = $this->baseRequest->withServerParams(['SERVER_PROTOCOL' => 'HTTP/1.1']);
        
        $this->assertEquals('1.1', $request->getProtocolVersion());
    }
    
    public function testWithProtocolVersion()
    {
        $request = $this->baseRequest->withProtocolVersion('1.1');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('1.1', $request->getProtocolVersion());
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid HTTP protocol version '0.2'
     */
    public function testWithInvalidProtocolVersion()
    {
        $this->baseRequest->withProtocolVersion('0.2');
    }
    
    
    public function testGetDefaultHeaders()
    {
        $headers = $this->baseRequest->getHeaders();
        $this->assertSame([], $headers);
    }
    
    public function testDetermineHeaders()
    {
        $request = $this->baseRequest->withServerParams([
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'CONTENT_TYPE' => 'text/plain',
            'CONTENT_LENGTH' => '20',
            'HTTP_HOST' => 'example.com',
            'HTTP_X_FOO' => 'bar',
            'HTTP_CONTENT_TYPE' => 'text/plain',
            'HTTPS' => 1
        ]);
        
        $this->assertEquals([
            'Content-Type' => ['text/plain'],
            'Content-Length' => ['20'],
            'Host' => ['example.com'],
            'X-Foo' => ['bar']
        ], $request->getHeaders());
    }
    
    public function testWithHeader()
    {
        $request = $this->baseRequest->withHeader('foo-zoo', 'red & blue');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['Foo-Zoo' => ['red & blue']], $request->getHeaders());
        
        return $request;
    }
    
    public function testWithHeaderArray()
    {
        $request = $this->baseRequest->withHeader('foo-zoo', ['red', 'blue']);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['Foo-Zoo' => ['red', 'blue']], $request->getHeaders());
        
        return $request;
    }
    
    /**
     * @depends testWithHeader
     */
    public function testWithHeaderAddAnother($origRequest)
    {
        $request = $origRequest->withHeader('QUX', 'white');
        $this->assertEquals([
            'Foo-Zoo' => ['red & blue'],
            'Qux' => ['white']
        ], $request->getHeaders());
    }
    
    /**
     * @depends testWithHeader
     */
    public function testWithHeaderOverwrite($origRequest)
    {
        $request = $origRequest->withHeader('foo-zoo', 'silver & gold');
        $this->assertEquals(['Foo-Zoo' => ['silver & gold']], $request->getHeaders());
    }
    
    public function testHasHeader()
    {
        $request = $this->baseRequest->withHeader('Foo', 'red');
        $this->assertInstanceof(ServerRequest::class, $request);
        
        $this->assertTrue($request->hasHeader('FoO'));
        $this->assertFalse($request->hasHeader('NotExists'));
    }
    
    public function testGetHeader()
    {
        $request = $this->baseRequest->withHeader('Foo', ['red', 'blue']);
        $this->assertInstanceof(ServerRequest::class, $request);
        
        $this->assertEquals(['red', 'blue'], $request->getHeader('FoO'));
    }
    
    public function testGetHeaderNotExists()
    {
        $this->assertEquals([], $this->baseRequest->getHeader('NotExists'));
    }
    
    public function testGetHeaderLine()
    {
        $request = $this->baseRequest->withHeader('Foo', ['red', 'blue']);
        $this->assertInstanceof(ServerRequest::class, $request);
        
        $this->assertEquals('red,blue', $request->getHeaderLine('FoO'));
    }
    
    public function testGetHeaderLineNotExists()
    {
        $this->assertEquals('', $this->baseRequest->getHeaderLine('NotExists'));
    }
    
    
    public function testGetDefaultBody()
    {
        $body = $this->baseRequest->getBody();
        
        $this->assertInstanceOf(Stream::class, $body);
        $this->assertEquals('data://text/plain,', $body->getMetadata('uri'));
    }
    
    public function testWithBody()
    {
        $stream = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();
        
        $request = $this->baseRequest->withBody($stream);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame($stream, $request->getBody());
    }
}
