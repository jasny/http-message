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
    protected $headers;

    public function setUp()
    {
        $refl = new \ReflectionProperty(Response::class, 'headers');
        $refl->setAccessible(true);
        
        $this->response = new Response();
        $this->headers = $this->getSimpleMock(HeaderObject::class);
        $refl->setValue($this->response, $this->headers);
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
        $this->headers->expects($this->once())
            ->method('withHeader')
            ->will($this->returnSelf());
        $this->headers->expects($this->once())
            ->method('getHeader')
            ->will($this->returnValue(['Foo' => ['Baz']]));;
        $newRequest = $this->response->withHeader('Foo', 'Baz');
        
        $this->assertInstanceof(Response::class, $newRequest);
        $this->assertSame(['Foo' => ['Baz']], $newRequest->getHeader('Foo'));
    }

    public function testHeadersAppend()
    {
        $this->headers->expects($this->once())
            ->method('withHeader')
            ->will($this->returnSelf());
        $request = $this->response->withHeader('Foo', 'Baz');
        
        
        $this->headers->expects($this->once())
            ->method('withAddedHeader')
            ->will($this->returnSelf());
        $secondRequest = $request->withAddedHeader('Qux', 'white');
        
        $this->headers->expects($this->atLeastOnce())
            ->method('hasHeader')
            ->will($this->returnValue(true));
        
        $this->assertTrue($secondRequest->hasHeader('Foo'));
        $this->assertTrue($secondRequest->hasHeader('Qux'));
        
        
        $this->headers->expects($this->once())
            ->method('getHeader')
            ->will($this->returnValue(['Foo' => ['Baz'], 'Qux' => ['white']]));
        $this->assertSame(['Foo' => ['Baz'], 'Qux' => ['white']], $secondRequest->getHeader('Qux'));
    }

    public function testRemoveHeaders()
    {
        $this->headers->expects($this->atLeastOnce())
            ->method('hasHeader')
            ->will($this->returnValue(false));
        $request = $this->response->withoutHeader('Foo');
        $this->assertFalse($request->hasHeader('Foo'));
    }

    public function testNotExistHeaders()
    {
        $this->headers->expects($this->atLeastOnce())
            ->method('hasHeader')
            ->will($this->returnValue(false));
        
        $request = $this->response->withHeader('Foo', 'Baz');
        $this->assertFalse($request->hasHeader('not-exist'));
    }

    public function testAppendValueToHeaders()
    {
        $request = $this->response->withHeader('Qux', 'blue');
        $secondRequest = $request->withAddedHeader('Qux', 'white');
        $this->headers->expects($this->exactly(2))
            ->method('hasHeader')
            ->will($this->returnValue(true));
        $this->assertTrue($request->hasHeader('Foo'));
        $this->assertTrue($request->hasHeader('Qux'));
        $this->headers->expects($this->once())
            ->method('getHeader')
            ->will($this->returnValue(['white', 'blue']));
        
        $this->assertSame(['white', 'blue'], $request->getHeader('Qux'));
    }

    public function testHeaderLine()
    {
        $this->headers->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->will($this->returnValue('white, blue'));
        
        $request = $this->response->withHeader('Qux', 'blue');
        $this->assertSame('white, blue', $request->getHeaderLine('Qux'));
    }

    public function testBody()
    {
        $body = $this->response->withBody($this->getSimpleMock(Stream::class));
        $this->assertInstanceof(Stream::class, $body->getBody());
    }
}
