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
    protected $request;

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
        $request = $this->response->withProtocolVersion('2.0');
        $this->assertEquals($request->getProtocolVersion(), '2');
        
        $request2 = $this->response->withProtocolVersion('1.1');
        $this->assertEquals($request2->getProtocolVersion(), '1.1');
        
        $request3 = $this->response->withProtocolVersion('1.0');
        $this->assertEquals($request3->getProtocolVersion(), '1.0');
    }

    public function testResponseProtocolFloat()
    {
        $request = $this->response->withProtocolVersion(2.0);
        $this->assertEquals($request->getProtocolVersion(), '2');
        
        $request2 = $this->response->withProtocolVersion(2);
        $this->assertEquals($request2->getProtocolVersion(), '2');
        
        $request3 = $this->response->withProtocolVersion(1.1);
        $this->assertEquals($request3->getProtocolVersion(), '1.1');
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
        $request = $this->response->withHeader('Serv', 'nginx/1.6.2');
        $this->assertTrue($request !== $this->response);
        $this->assertTrue($request->hasHeader('Serv'));
    }
    
    public function testGetHeader()
    {
        $request = $this->response->withHeader('Serv', 'nginx/1.6.2');
        $this->assertEquals(array('nginx/1.6.2'), $request->getHeader('Serv'));
        $this->assertEquals('nginx/1.6.2', $request->getHeaderLine('Serv'));
    }
    
    public function testGetHeaderLine()
    {
        $request = $this->response->withHeader('Serv', 'nginx/1.6.2');
        $this->assertEquals('nginx/1.6.2', $request->getHeaderLine('Serv'));
    }

    public function testEmptyHeadersIntoOldObject()
    {
        $this->assertEmpty($this->response->getHeaders());
        $this->assertFalse($this->response->hasHeader('Serv'));
    }

    public function testHeaderMultipleValuesGetHeaderLine()
    {
        $request = $this->response->withHeader('Data', array('bar','foo'));
        $this->assertEquals('bar,foo', $request->getHeaderLine('Data'));
    }

    public function testAppendAnotherHeadersIntoOldObject()
    {
        $request = $this->response->withHeader('Data', array(
            'bar',
            'foo'
        ));
        
        $request = $request->withAddedHeader('Data', 'new');
        $this->assertTrue($request->hasHeader('Data'));
        $this->assertEquals(array(
            'bar',
            'foo',
            'new'
        ), $request->getHeader('Data'));
        $this->assertEquals('bar,foo,new', $request->getHeaderLine('Data'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testStatusCode()
    {
        $this->assertSame(200, $this->response->getStatusCode());
        $this->assertSame('OK', $this->response->getReasonPhrase());
        $this->expectException($this->response->withStatus(1020.20));
        $this->expectException($this->response->withStatus(1020));
        $request = $this->response->withStatus(404, 'Some unique status');
        $this->assertSame(404, $request->getStatusCode());
        $this->assertSame('Some unique status', $request->getReasonPhrase());
        $this->assertSame('404 Some unique status', $request->getStatusString());
        $request = $this->response->withStatus(404, '');
        $this->assertSame(404, $request->getStatusCode());
        $this->assertSame('Not Found', $request->getReasonPhrase());
        $request = $this->response->withStatus(999);
        $this->assertSame(999, $request->getStatusCode());
        $this->assertSame('', $request->getReasonPhrase());
    }

    public function testBody()
    {
        $body = $this->response->withBody($this->getSimpleMock(Stream::class));
        $this->assertInstanceof(Stream::class, $body->getBody());
    }
}
