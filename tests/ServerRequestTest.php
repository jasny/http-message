<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use PHPUnit_Framework_TestCase;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\Stream;
use Jasny\HttpMessage\Uri;

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
    
    public function testWithSuperGlobalsReset()
    {
        $request = $this->baseRequest
            ->withMethod('POST')
            ->withSuperGlobals();
        
        $this->assertEquals('', $request->getMethod());
    }
    
    
    public function testGetDefaultServerParams()
    {
        $this->assertSame([], $this->baseRequest->getServerParams());
    }
    
    public function testWithServerParams()
    {
        $params = [
            'SERVER_SOFTWARE' => 'Foo 1.0',
            'COLOR' => 'red',
            'SCRIPT_FILENAME' => 'qux.php'
        ];
        
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
        $request = $this->baseRequest
            ->withMethod('POST')
            ->withServerParams([]);
        
        $this->assertEquals('', $request->getMethod());
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
    
    public function testWithProtocolVersionFloat()
    {
        $request = $this->baseRequest->withProtocolVersion(2.0);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('2.0', $request->getProtocolVersion());
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
    
    
    public function testGetDefaultRequestTarget()
    {
        $this->assertEquals('/', $this->baseRequest->getRequestTarget());
    }
    
    public function testDetermineRequestTarget()
    {
        $request = $this->baseRequest->withServerParams(['REQUEST_URI' => '/foo?bar=1']);
        $this->assertEquals('/foo?bar=1', $request->getRequestTarget());
    }
    
    public function testDetermineRequestTargetOrigin()
    {
        $request = $this->baseRequest->withServerParams(['REQUEST_METHOD' => 'OPTIONS']);
        $this->assertEquals('*', $request->getRequestTarget());
    }
    
    public function testWithRequestTarget()
    {
        $request = $this->baseRequest->withRequestTarget('/foo?bar=99');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('/foo?bar=99', $request->getRequestTarget());
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Request target should be a string, not a stdClass object
     */
    public function testWithRequestTargetWithInvalidArgument()
    {
        $this->baseRequest->withRequestTarget((object)['foo' => 1, 'bar' => 2, 'zoo' => 3]);
    }
    
    
    public function testGetDefaultMethod()
    {
        $this->assertSame('', $this->baseRequest->getMethod());
    }
    
    public function testDetermineMethod()
    {
        $request = $this->baseRequest->withServerParams(['REQUEST_METHOD' => 'post']);
        $this->assertEquals('POST', $request->getMethod());
    }
    
    public function testWithMethod()
    {
        $request = $this->baseRequest->withMethod('GeT');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('GET', $request->getMethod());
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid method 'foo bar': Method may only contain letters and dashes
     */
    public function testWithInvalidMethod()
    {
        $this->baseRequest->withMethod("foo bar");
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Method should be a string, not a stdClass object
     */
    public function testWithMethodWithInvalidArgument()
    {
        $this->baseRequest->withMethod((object)['foo' => 1, 'bar' => 2]);
    }
    
    
    public function testGetDefaultUri()
    {
        $uri = $this->baseRequest->getUri();
        
        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertEquals(new Uri(), $uri);
    }
    
    public function testDetermineUri()
    {
        $request = $this->baseRequest->withServerParams([
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PWD' => 'secure',
            'HTTP_HOST' => 'www.example.com',
            'SERVER_PORT' => 80,
            'PATH_INFO' => '/page/bar',
            'QUERY_STRING' => 'color=red'
        ]);
        
        $this->assertEquals(new Uri([
            'scheme' => 'http',
            'user' => 'foo',
            'password' => 'secure',
            'host' => 'www.example.com',
            'port' => 80,
            'path' => '/page/bar',
            'query' => 'color=red'
        ]), $request->getUri());
    }
    
    public function testDetermineUriHttps()
    {
        $protocol = ['SERVER_PROTOCOL' => 'HTTP/1.1'];
        $request = $this->baseRequest;
        
        $this->assertEquals('http', $request->withServerParams($protocol)->getUri()->getScheme());
        $this->assertEquals('http', $request->withServerParams($protocol + ['HTTPS' => ''])->getUri()->getScheme());
        $this->assertEquals('http', $request->withServerParams($protocol + ['HTTPS' => 'off'])->getUri()->getScheme());
        
        $this->assertEquals('https', $request->withServerParams($protocol + ['HTTPS' => '1'])->getUri()->getScheme());
        $this->assertEquals('https', $request->withServerParams($protocol + ['HTTPS' => 'on'])->getUri()->getScheme());
    }
    
    public function testWithUri()
    {
        $uri = $this->getMockBuilder(Uri::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHost'])
            ->disableProxyingToOriginalMethods()
            ->getMock();
        
        $uri->expects($this->once())->method('getHost')->willReturn('www.example.com');
        
        $request = $this->baseRequest->withUri($uri);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame($uri, $request->getUri());
        $this->assertEquals(['www.example.com'], $request->getHeader('Host'));
    }
    
    public function testWithUriPreserveHost()
    {
        $uri = $this->getMockBuilder(Uri::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHost'])
            ->disableProxyingToOriginalMethods()
            ->getMock();
        
        $uri->expects($this->never())->method('getHost');
        
        $request = $this->baseRequest->withUri($uri, true);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame($uri, $request->getUri());
        $this->assertEquals([], $request->getHeader('Host'));
    }
    
    
    public function testGetDefaultCookieParams()
    {
        $this->assertSame([], $this->baseRequest->getCookieParams());
    }
    
    public function testWithCookieParams()
    {
        $request = $this->baseRequest->withCookieParams(['foo' => 'bar', 'color' => 'red']);
        $this->assertSame(['foo' => 'bar', 'color' => 'red'], $request->getCookieParams());
    }
    
    
    public function testGetDefaultQueryParams()
    {
        $this->assertSame([], $this->baseRequest->getQueryParams());
    }
    
    public function testWithQueryParams()
    {
        $request = $this->baseRequest->withQueryParams(['foo' => 'bar', 'color' => 'red']);
        $this->assertSame(['foo' => 'bar', 'color' => 'red'], $request->getQueryParams());
    }
}
