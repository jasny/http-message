<?php
namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use Jasny\HttpMessage\Tests\AssertLastError;
use Jasny\HttpMessage\Response;

/**
 * @covers Jasny\HttpMessage\Response
 * @covers Jasny\HttpMessage\Response\protocolVersion
 * @covers Jasny\HttpMessage\Response\StatusCode
 * @covers Jasny\HttpMessage\Response\Headers
 * @covers Jasny\HttpMessage\Response\Body
 */
class ResponseTest extends PHPUnit_Framework_TestCase
{
    use AssertLastError;

    /**
     *
     * @var ServerRequest
     */
    protected $response;

    public function setUp()
    {
        $this->response = new Response();
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

    public function testResponceClass()
    {
        $this->assertInstanceof(Response::class, $this->response);
    }

    public function testResponseProtocolDefaultValue()
    {
        $this->assertEquals('1.1', $this->response->getProtocolVersion());
    }

    public function testResponseProtocol()
    {
        $response = $this->response->withProtocolVersion('2.0');
        $this->assertEquals('2', $response->getProtocolVersion());
        
        $response2 = $this->response->withProtocolVersion('1.1');
        $this->assertEquals('1.1', $response2->getProtocolVersion());
        
        $response3 = $this->response->withProtocolVersion('1.0');
        $this->assertEquals('1.0', $response3->getProtocolVersion());
    }

    public function testIstantResponceClass()
    {
        $response = $this->response->withProtocolVersion('1.1');
        $this->assertInstanceof(Response::class, $response);
        $this->assertInstanceof(Response::class, $response);
    }

    public function testResponseProtocolFloat()
    {
        $response = $this->response->withProtocolVersion(2.0);
        $this->assertEquals('2', $response->getProtocolVersion());
        
        $response2 = $this->response->withProtocolVersion(2);
        $this->assertEquals('2', $response2->getProtocolVersion());
        
        $response3 = $this->response->withProtocolVersion(1.1);
        $this->assertEquals('1.1', $response3->getProtocolVersion());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage HTTP versions 0.1 are unknown
     */
    public function testFalseValueResponseProtocol()
    {
        $this->response->withProtocolVersion(0.1);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage HTTP version must be a string or float
     */
    public function testFalseTypeResponseProtocol()
    {
        $this->response->withProtocolVersion(array(
            '1.0',
            '1.1'
        ));
    }

    public function testHeadersEmpty()
    {
        $this->assertSame(array(), $this->response->getHeaders());
    }

    public function testAppendHeaders()
    {
        $response = $this->response->withHeader('Serv', 'nginx/1.6.2');
        $this->assertTrue($response !== $this->response);
        $this->assertTrue($response->hasHeader('Serv'));
        
        return $response;
    }

    /**
     * @depends testAppendHeaders
     */
    public function testGetHeader(Response $response)
    {
        $this->assertEquals(array(
            'nginx/1.6.2'
        ), $response->getHeader('Serv'));
        $this->assertEquals('nginx/1.6.2', $response->getHeaderLine('Serv'));
    }

    /**
     * @depends testAppendHeaders
     */
    public function testGetHeaderLine(Response $response)
    {
        $response = $this->response->withHeader('Serv', 'nginx/1.6.2');
        $this->assertEquals('nginx/1.6.2', $response->getHeaderLine('Serv'));
    }

    public function testEmptyHeadersOnInitObject()
    {
        $this->assertEmpty($this->response->getHeaders());
        $this->assertFalse($this->response->hasHeader('Serv'));
    }

    public function testHeaderMultipleValuesGetHeaderLine()
    {
        $response = $this->response->withHeader('Data', array(
            'bar',
            'foo'
        ));
        $this->assertEquals('bar,foo', $response->getHeaderLine('Data'));
    }

    /**
     * @depends testAppendHeaders
     */
    public function testAppendAnotherHeaders(Response $responseWithHeader)
    {
        $response = $responseWithHeader->withHeader('Data', array(
            'bar',
            'foo'
        ));
        $this->assertTrue($response->hasHeader('Serv'));
        $this->assertTrue($response->hasHeader('Data'));
    }

    public function testAppendHeadersAnotherValue()
    {
        $response = $this->response->withHeader('Data', array(
            'bar',
            'foo'
        ));
        
        $response = $response->withAddedHeader('Data', 'new');
        $this->assertTrue($response->hasHeader('Data'));
        $this->assertEquals(array(
            'bar',
            'foo',
            'new'
        ), $response->getHeader('Data'));
        $this->assertEquals('bar,foo,new', $response->getHeaderLine('Data'));
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
    public function testWrongTypeStatusCode()
    {
        $this->expectException($this->response->withStatus(1020.20));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWrongValueStatusCode()
    {
        $this->expectException($this->response->withStatus(1020));
    }

    public function testBody()
    {
        $body = $this->response->withBody($this->getSimpleMock(Stream::class));
        $this->assertInstanceof(Stream::class, $body->getBody());
    }
}
