<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use Jasny\HttpMessage\Tests\AssertLastError;
use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\ResponseHeaders;
use Jasny\HttpMessage\Headers;

/**
 * @covers Jasny\HttpMessage\Response
 * @covers Jasny\HttpMessage\Response\GlobalEnvironment
 * @covers Jasny\HttpMessage\Response\ProtocolVersion
 * @covers Jasny\HttpMessage\Message\ProtocolVersion
 * @covers Jasny\HttpMessage\Response\StatusCode
 * @covers Jasny\HttpMessage\Response\Headers
 * @covers Jasny\HttpMessage\Message\Headers
 * @covers Jasny\HttpMessage\Response\Body
 * @covers Jasny\HttpMessage\Message\Body
 */
class ResponseTest extends PHPUnit_Framework_TestCase
{
    use AssertLastError;
    
    /**
     * @var Response
     */
    protected $baseResponse;
    
    /**
     * @var ReflectionProperty
     */
    protected $refl;
    /**
     * @var Headers|\PHPUnit_Framework_MockObject_MockObject;
     */
    protected $headers;

    public function setUp()
    {
        $this->refl = new \ReflectionProperty(Response::class, 'headers');
        $this->refl->setAccessible(true);
        
        $this->baseResponse = new Response();
        $this->headers = $this->createMock(Headers::class);
        $this->refl->setValue($this->baseResponse, $this->headers);
    }

    public function testWithGlobalEnvironment(){
        
        $response = $this->baseResponse->withGlobalEnvironment();
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $this->assertTrue($this->baseResponse->isStale());
        
        $this->refl = new \ReflectionProperty($response, 'headers');
        $this->refl->setAccessible(true);
        $this->assertInstanceof(ResponseHeaders::class, $this->refl->getValue($response));
        $this->assertInstanceof(OutputBufferStream::class, $response->getBody());
        $this->assertEquals('php://output', $response->getBody()->getMetadata('uri'));
    }

    public function testWithoutGlobalEnvironment()
    {
        $response = $this->baseResponse->withGlobalEnvironment()->withoutGlobalEnvironment();
        $this->refl->setAccessible(true);
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        $this->refl = new \ReflectionProperty($response, 'headers');
        $this->refl->setAccessible(true);
        $this->assertInstanceof(Headers::class, $this->refl->getValue($response));
        $this->assertInstanceof(OutputBufferStream::class, $response->getBody());
        $this->assertEquals('php://temp', $response->getBody()->getMetadata('uri'));
    }

    public function testChangeProtocolVersion()
    {
        $response2 = $this->baseResponse->withProtocolVersion('2');
        $this->assertEquals('2', $response2->getProtocolVersion());
        
        $response11 = $response2->withProtocolVersion('1.1');
        $this->assertEquals('1.1', $response11->getProtocolVersion());
        
        $response10 = $response11->withProtocolVersion('1.0');
        $this->assertEquals('1.0', $response10->getProtocolVersion());
    }

    public function testWithProtocolVersionImmutable()
    {
        $version = $this->baseResponse->getProtocolVersion();
        $response = $this->baseResponse->withProtocolVersion('1.1');
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $this->assertEquals($version, $this->baseResponse->getProtocolVersion());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid HTTP protocol version '0.1'
     */
    public function testInvalidValueProtocolVersion()
    {
        $this->baseResponse->withProtocolVersion('0.1');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage HTTP version must be a string
     */
    public function testInvalidTypeProtocolVersion()
    {
        $this->baseResponse->withProtocolVersion(['1.0', '1.1']);
    }

    public function testStatusCodeDefaults()
    {
        $this->assertSame(200, $this->baseResponse->getStatusCode());
        $this->assertSame('OK', $this->baseResponse->getReasonPhrase());
    }

    public function testStatusCodeChangeByRCF()
    {
        $response = $this->baseResponse->withStatus(404);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testStatusCodeChangeWithMessage()
    {
        $response = $this->baseResponse->withStatus(404, 'Some unique status');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Some unique status', $response->getReasonPhrase());
        $this->assertSame('404 Some unique status', $response->getStatusString());
    }

    public function testNotStandardStatusCode()
    {
        $response = $this->baseResponse->withStatus(999);
        $this->assertSame(999, $response->getStatusCode());
        $this->assertSame('', $response->getReasonPhrase());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidTypeStatusCode()
    {
        $this->baseResponse->withStatus(1020.20);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidValueStatusCode()
    {
        $this->baseResponse->withStatus(1020);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidValueStatusPhrase()
    {
        $this->baseResponse->withStatus(200, ['foo', 'bar']);
    }

    
    public function testDetermineHeaders()
    {
        $response = new Response();
        $this->assertEquals([], $response->getHeaders());
    }
    
    public function testWithHeader()
    {
       $this->headers->expects($this->once())
            ->method('withHeader')
            ->will($this->returnSelf());
        
        $response = $this->baseResponse->withHeader('Foo', 'Baz');
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
    }

    public function testWithAddedHeader()
    {
        $this->headers->expects($this->once())
            ->method('withAddedHeader')
            ->will($this->returnSelf());
        
        $response = $this->baseResponse->withAddedHeader('Foo', 'Baz');
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
    }

    public function testWithoutHeader()
    {
        $this->headers->expects($this->once())
            ->method('withoutHeader')
            ->will($this->returnSelf());

        $this->headers->expects($this->atLeastOnce())
            ->method('hasHeader')
            ->with('Foo')
            ->willReturn(true);
        
        $response = $this->baseResponse->withoutHeader('Foo');
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
    }

    public function testWithoutNonExistantHeader()
    {
        $this->headers->expects($this->never())
            ->method('withoutHeader')
            ->will($this->returnSelf());

        $this->headers->expects($this->atLeastOnce())
            ->method('hasHeader')
            ->with('Foo')
            ->willReturn(false);
        
        $response = $this->baseResponse->withoutHeader('Foo');
        
        $this->assertSame($this->baseResponse, $response);
    }

    public function testHasHeader()
    {
        $this->headers->expects($this->once())
            ->method('hasHeader')
            ->with('Foo')
            ->willReturn(true);
        
        $this->assertTrue($this->baseResponse->hasHeader('Foo'));
    }

    public function testGetHeader()
    {
        $this->headers->expects($this->once())
            ->method('getHeader')
            ->with('Foo')
            ->willReturn(['Baz', 'Car']);
        
        $this->assertSame(['Baz', 'Car'], $this->baseResponse->getHeader('Foo'));
    }

    public function testGetHeaderLine()
    {
        $this->headers->expects($this->once())
            ->method('getHeaderLine')
            ->with('Foo')
            ->will($this->returnValue('Baz'));
        
        $this->assertSame('Baz', $this->baseResponse->getHeaderLine('Foo'));
    }

    
    /**
     * @internal `createDefaultBody()` is thighly coupled, meaning `Stream::getMetadata()` must be working properly
     */
    public function testGetDefaultBody()
    {
        $body = $this->baseResponse->getBody();
        
        $this->assertInstanceOf(Stream::class, $body);
        $this->assertEquals('php://temp', $body->getMetadata('uri'));
    }
    
    public function testWithBody()
    {
        $body = $this->createMock(Stream::class);
        
        $response = $this->baseResponse->withBody($body);
        $this->assertSame($body, $response->getBody());
    }
}
