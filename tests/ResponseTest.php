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

    /**
     *
     * @var example http_response
     */
    protected $http_response = "";

    public function setUp()
    {
        $this->response = new Response();
    }

    public function testResponceClass()
    {
        $this->assertInstanceof(Response::class, $this->response);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testResponseProtocol()
    {
        $this->assertTrue('1.1' == $this->response->getProtocolVersion());
        $request = $this->response->withProtocolVersion('2.0');
        $this->expectException($this->response->withProtocolVersion('3.0'));
        $this->assertTrue($request->getProtocolVersion() === 2);
        $request = $this->response->withProtocolVersion('2');
        $this->assertTrue($request->getProtocolVersion() === '2');
        $this->expectException($this->response->withProtocolVersion(array(
            3.0
        )));
        $request = $this->response->withProtocolVersion('1.0');
        $this->assertTrue($request->getProtocolVersion() === '1.0');
    }

    public function testHeadersEmpty()
    {
        $this->assertEquals(array(), $this->response->getHeaders());
    }

    public function testAppendHeaders()
    {
        $request = $this->response->withHeader('Serv', 'nginx/1.6.2');
        $this->assertTrue($request !== $this->response);
        $this->assertTrue($request !== $this->response);
        $this->assertTrue($request->hasHeader('Serv'));
        $this->assertEquals(array(
            'nginx/1.6.2'
        ), $request->getHeader('Serv'));
        $this->assertEquals('nginx/1.6.2', $request->getHeaderLine('Serv'));
    }

    public function testEmptyHeadersIntoOldObject()
    {
        $this->assertFalse($this->response->getHeaders() == array(
            'Server' => array(
                'nginx/1.6.2'
            )
        ));
        $this->assertTrue($this->response->getHeaders() == array());
        $this->assertFalse($this->response->hasHeader('Serv'));
    }

    public function testAppendAnotherHeadersIntoOldObject()
    {
        $this->assertFalse($this->response->hasHeader('Serv'));
        $request = $this->response->withHeader('Data', array(
            'bar',
            'foo'
        ));
        
        $this->assertTrue($request->hasHeader('Data'));
        $this->assertFalse($request->hasHeader('Serv'));
        
        $this->assertEquals(array(
            'bar',
            'foo'
        ), $request->getHeader('Data'));
        $this->assertEquals('bar,foo', $request->getHeaderLine('Data'));
        
        
        $requestTwo = $request->withAddedHeader('Data', 'new');
        $this->assertTrue($requestTwo->hasHeader('Data'));
        $this->assertEquals(array(
            'bar',
            'foo',
            'new'
        ), $requestTwo->getHeader('Data'));
        $this->assertEquals('bar,foo,new', $requestTwo->getHeaderLine('Data'));
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
        $this->assertSame($this->response->getBody()
            ->getContents(), '');
        $this->assertSame($this->response->getBody()
            ->getSize(), 0);
        $this->assertTrue($this->response->getBody()
            ->isReadable());
        $this->assertTrue($this->response->getBody()
            ->isWritable());
        $body = $this->response->getBody();
        $this->assertTrue($this->response->getBody()
            ->isWritable());
        $string = 'Cool string!';
        $this->response->getBody()->write($string);
        $this->response->getBody()->rewind();
        $this->assertSame($this->response->getBody()
            ->getContents(), $string);
        $stringTwo = ' All ok!';
        $this->response->getBody()->write($stringTwo);
        $this->response->getBody()->rewind();
        $this->assertSame($this->response->getBody()
            ->getContents(), $string . $stringTwo);
    }
}
