<?php

namespace Jasny\HttpMessage\ServerRequest;

use PHPUnit_Framework_TestCase;
use Jasny\TestHelper;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\Uri;
use Jasny\HttpMessage\Headers;
use Jasny\HttpMessage\HeadersInterface;

/**
 * @covers Jasny\HttpMessage\ServerRequest\Uri
 */
class UriTest extends PHPUnit_Framework_TestCase
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
        $this->baseRequest = new ServerRequest();
        
        $this->headers = $this->createMock(Headers::class);
        $this->setPrivateProperty($this->baseRequest, 'headers', $this->headers);
    }
    

    public function testGetUriDefault()
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
            'HTTP_HOST' => 'www.example.com:8080',
            'SERVER_PORT' => 8080,
            'REQUEST_URI' => '/page/bar?color=red',
            'QUERY_STRING' => 'color=red'
        ]);
        
        $this->assertEquals(new Uri([
            'scheme' => 'http',
            'user' => 'foo',
            'password' => 'secure',
            'host' => 'www.example.com',
            'port' => 8080,
            'path' => '/page/bar',
            'query' => 'color=red'
        ]), $request->getUri());
    }
    

    public function testDetermineUriHttps()
    {
        $protocol = ['SERVER_PROTOCOL' => 'HTTP/1.1'];
        $request = $this->baseRequest;
        
        $this->assertEquals('http', $request->withServerParams($protocol)
            ->getUri()
            ->getScheme());
        $this->assertEquals('http', $request->withServerParams($protocol + ['HTTPS' => ''])
            ->getUri()
            ->getScheme());
        $this->assertEquals('http', $request->withServerParams($protocol + ['HTTPS' => 'off'])
            ->getUri()
            ->getScheme());
        
        $this->assertEquals('https', $request->withServerParams($protocol + ['HTTPS' => '1'])
            ->getUri()
            ->getScheme());
        $this->assertEquals('https', $request->withServerParams($protocol + ['HTTPS' => 'on'])
            ->getUri()
            ->getScheme());
    }

    public function testWithUri()
    {
        $uri = $this->createMock(Uri::class);
        $uri->expects($this->once())
            ->method('getHost')
            ->willReturn('www.example.com');
        
        $this->headers->expects($this->once())
            ->method('withHeader')
            ->with('Host', 'www.example.com')
            ->will($this->returnSelf());
        
        $request = $this->baseRequest->withUri($uri);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame($uri, $request->getUri());
    }

    public function testWithUriPreserveHost()
    {
        $uri = $this->createMock(Uri::class);
        $uri->expects($this->never())
            ->method('getHost');
        
        $this->headers->expects($this->never())
            ->method('withHeader');
        
        $request = $this->baseRequest->withUri($uri, true);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame($uri, $request->getUri());
    }
}
