<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use Jasny\HttpMessage\Tests\AssertLastError;
use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\Headers as HeaderObject;

/**
 * @covers Jasny\HttpMessage\Response
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
    protected $response;

    public function setUp()
    {
        $refl = new \ReflectionProperty(Response::class, 'headers');
        $refl->setAccessible(true);
        
        $this->response = new Response();
        $refl->setValue($this->response, $this->getSimpleMock(HeaderObject::class));
        $this->response->initHeaders();
    }

    /**
     * Get mock with original methods and constructor disabled
     *
     * @param string $classname            
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSimpleMock($classname)
    {
        return $this->getMockBuilder($classname)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->disableOriginalClone()
            ->getMock();
    }

    public function testProtocolVersionDefaultValue()
    {
        $this->assertEquals('1.1', $this->response->getProtocolVersion());
    }

    public function testChangeProtocolVersion()
    {
        $response2 = $this->response->withProtocolVersion('2');
        $this->assertEquals('2', $response2->getProtocolVersion());
        
        $response11 = $response2->withProtocolVersion('1.1');
        $this->assertEquals('1.1', $response11->getProtocolVersion());
        
        $response10 = $response11->withProtocolVersion('1.0');
        $this->assertEquals('1.0', $response10->getProtocolVersion());
    }

    public function testWithProtocolVersionImmutable()
    {
        $version = $this->response->getProtocolVersion();
        $response = $this->response->withProtocolVersion('1.1');
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->response, $response);
        
        $this->assertEquals($version, $this->response->getProtocolVersion());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid HTTP protocol version '0.1'
     */
    public function testInvalidValueProtocolVersion()
    {
        $this->response->withProtocolVersion('0.1');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage HTTP version must be a string
     */
    public function testInvalidTypeProtocolVersion()
    {
        $this->response->withProtocolVersion(['1.0', '1.1']);
    }

    public function testStatusCodeDefaults()
    {
        $this->assertSame(200, $this->response->getStatusCode());
        $this->assertSame('OK', $this->response->getReasonPhrase());
    }

    public function testStatusCodeChangeByRCF()
    {
        $response = $this->response->withStatus(404);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testStatusCodeChangeWithMessage()
    {
        $response = $this->response->withStatus(404, 'Some unique status');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Some unique status', $response->getReasonPhrase());
        $this->assertSame('404 Some unique status', $response->getStatusString());
    }

    public function testNotStandardStatusCode()
    {
        $response = $this->response->withStatus(999);
        $this->assertSame(999, $response->getStatusCode());
        $this->assertSame('', $response->getReasonPhrase());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidTypeStatusCode()
    {
        $this->response->withStatus(1020.20);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidValueStatusCode()
    {
        $this->response->withStatus(1020);
    }

    public function testHeadersAdd()
    {
        $newRequest = $this->response->withHeader('Foo', 'Baz');
        
        $this->assertTrue($newRequest->hasHeader('Foo'));
        $this->assertSame(['Baz'], $newRequest->getHeader('Foo'));
        
        return $newRequest;
    }

    /**
     *
     * @depends testHeadersAdd
     */
    public function testHeadersAppend(Response $request)
    {
        $secondRequest = $request->withAddedHeader('Qux', 'white');
        $this->assertTrue($secondRequest->hasHeader('Foo'));
        $this->assertTrue($secondRequest->hasHeader('Qux'));
        $this->assertSame(['white'], $secondRequest->getHeader('Qux'));
        
        return $secondRequest;
    }

    /**
     *
     * @depends testHeadersAppend
     */
    public function testRemoveHeaders(Response $request)
    {
        $secondRequest = $request->withoutHeader('Foo');
        $this->assertFalse($secondRequest->hasHeader('Foo'));
        $this->assertTrue($secondRequest->hasHeader('Qux'));
    }

    /**
     *
     * @depends testHeadersAppend
     */
    public function testNotExistHeaders(Response $request)
    {
        $this->assertFalse($request->hasHeader('not-exist'));
    }

    /**
     *
     * @depends testHeadersAppend
     */
    public function testAppendValueToHeaders(Response $request)
    {
        $secondRequest = $request->withAddedHeader('Qux', 'blue');
        $this->assertTrue($secondRequest->hasHeader('Foo'));
        $this->assertTrue($secondRequest->hasHeader('Qux'));
        $this->assertSame(['white', 'blue'], $secondRequest->getHeader('Qux'));
        
        return $secondRequest;
    }

    /**
     *
     * @depends testAppendValueToHeaders
     */
    public function testHeaderLine(Response $request)
    {
        $this->assertSame('white, blue', $request->getHeaderLine('Qux'));
    }

    public function testBody()
    {
        $body = $this->response->withBody($this->getSimpleMock(Stream::class));
        $this->assertInstanceof(Stream::class, $body->getBody());
    }
}
