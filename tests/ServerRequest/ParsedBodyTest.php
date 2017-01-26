<?php

namespace Jasny\HttpMessage\ServerRequest;

use PHPUnit_Framework_TestCase;
use Jasny\TestHelper;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\Stream;

/**
 * @covers Jasny\HttpMessage\ServerRequest\ParsedBody
 */
class ParsedBodyTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    /**
     * @var ServerRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $baseRequest;
    
    public function setUp()
    {
        $refl = new \ReflectionProperty(ServerRequest::class, 'headers');
        $refl->setAccessible(true);
        
        $this->baseRequest = $this->getMockBuilder(ServerRequest::class)
            ->setMethods(['getHeaderLine', 'withHeader'])->getMock();
    }
    
    protected function setContentType($contentType)
    {
        $this->baseRequest->method('getHeaderLine')->with('Content-Type')->willReturn($contentType);
    }

    
    public function testGetParsedBodyDefault()
    {
        $this->assertNull($this->baseRequest->getParsedBody());
    }

    public function testParseUrlEncodedBody()
    {
        $this->setContentType('application/x-www-form-urlencoded');
        
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('foo=bar&color=red');
        
        $request = $this->baseRequest->withBody($body);
        
        $this->assertEquals(['foo' => 'bar', 'color' => 'red'], $request->getParsedBody());
        
        return $request;
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Parsing multipart/form-data isn't supported
     */
    public function testParseMultipartBody()
    {
        $this->setContentType('multipart/form-data');
        $this->baseRequest->getParsedBody();
    }

    public function jsonHeaderProvider()
    {
        return [
            ['application/json'],
            ['application/json; charset=utf-8']
        ];
    }
    
    /**
     * @dataProvider jsonHeaderProvider
     * 
     * @param string $header
     * @return ServerRequest
     */
    public function testParseJsonBody($header)
    {
        $this->setContentType($header);
        
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('{"foo":"bar","color":"red"}');
        
        $request = $this->baseRequest->withBody($body);
        
        $this->assertEquals(['foo' => 'bar', 'color' => 'red'], $request->getParsedBody());
    }

    public function testParseInvalidJsonBody()
    {
        $this->setContentType('application/json');
        
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('not json');
        
        $request = $this->baseRequest->withBody($body);
        
        $this->assertNull(@$request->getParsedBody());
        $this->assertLastError(E_USER_WARNING, 'Failed to parse json body: Syntax error');
    }

    public function testParseXmlBody()
    {
        if (!function_exists('simplexml_load_string')) {
            return $this->markTestSkipped('SimpleXML extension not loaded');
        }

        $this->setContentType('text/xml');
        
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('<foo>bar</foo>');
        
        $request = $this->baseRequest->withBody($body);
        $parsedBody = $request->getParsedBody();
        
        $this->assertInstanceOf(\SimpleXMLElement::class, $parsedBody);
        $this->assertXmlStringEqualsXmlString('<foo>bar</foo>', $parsedBody->asXML());
    }

    public function testParseInvalidXmlBody()
    {
        $this->setContentType('text/xml');
        
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('not xml');
        
        $request = $this->baseRequest->withBody($body);
        
        $this->assertNull(@$request->getParsedBody());
        $this->assertLastError(E_WARNING);
    }

    public function testParseUnknownBody()
    {
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('getSize')
            ->willReturn(4);
        
        $request = $this->baseRequest->withBody($body);
        
        $this->assertNull($request->getParsedBody());
    }

    public function testParseUnsupportedBody()
    {
        $this->setContentType('application/x-foo');
        
        $this->assertNull($this->baseRequest->getParsedBody());
    }

    /**
     * @depends testParseUrlEncodedBody
     */
    public function testResetParsedBody(ServerRequest $originalRequest)
    {
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('foo=do&color=blue'); // Same size
        

        $request = $originalRequest->withBody($body);
        $this->assertEquals(['foo' => 'do', 'color' => 'blue'], $request->getParsedBody());
    }

    /**
     * ServerRequest::setPostData is protected, because it should only be used for $_POST
     */
    public function testPostNoParse()
    {
        $data = ['foo' => 'bar'];
        
        $this->setContentType('application/x-www-form-urlencoded; charset=utf-8');
        
        $body = $this->createMock(Stream::class);
        $body->expects($this->never())->method('__toString');
        
        $refl = new \ReflectionMethod(ServerRequest::class, 'setPostData');
        $refl->setAccessible(true);
        
        $request = $this->baseRequest->withBody($body);
        
        $refl->invokeArgs($request, [&$data]);
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
        
        $newRequest = $request->withParsedBody(['zoo' => 'qux']);
        
        $this->assertInstanceOf(ServerRequest::class, $newRequest);
        $this->assertEquals(['zoo' => 'qux'], $newRequest->getParsedBody());
    }

    /**
     * ServerRequest::setPostData is protected, because it should only be used for $_POST
     */
    public function testPostVsJson()
    {
        $data = ['zoo' => 'qux'];
        
        $this->setContentType('application/json; charset=utf-8');
        
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('{"foo": "bar"}');
        
        $refl = new \ReflectionMethod(ServerRequest::class, 'setPostData');
        $refl->setAccessible(true);
        
        $request = $this->baseRequest->withBody($body);
        
        $refl->invokeArgs($request, [&$data]); // Should have no effect
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
        
        $refl->invokeArgs($request, [&$data]); // Should still have no effect
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
    }

    public function testWithParsedBody()
    {
        $request = $this->baseRequest->withParsedBody(['foo' => 'bar']);
        
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
    }

    public function testWithParsedBodyNoReset()
    {
        $this->setContentType('application/json');

        $body = $this->createMock(Stream::class);
        $body->expects($this->never())
            ->method('__toString');
        
        $body->expects($this->never())
            ->method('getSize');
        
        $request = $this->baseRequest->withBody($body)->withParsedBody(['foo' => 'bar']);
        
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
    }

    public function testWithParsedBodySetNull()
    {
        $this->setContentType('application/json');

        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('{"foo": "bar"}');

        $request = $this->baseRequest->withBody($body)->withParsedBody(['foo' => 'qux']);
        $this->assertEquals(['foo' => 'qux'], $request->getParsedBody());
        
        $nextRequest = $request->withParsedBody(null);
        $this->assertEquals(['foo' => 'bar'], $nextRequest->getParsedBody());
    }

    public function testWithParsedBodyTurnStale()
    {
        $refl = new \ReflectionProperty(ServerRequest::class, 'isStale');
        $refl->setAccessible(true);
        $refl->setValue($this->baseRequest, false);
        
        $request = $this->baseRequest->withParsedBody([]);
        
        $this->assertTrue($this->baseRequest->isStale());
        $this->assertFalse($request->isStale());
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Failed to parse json body: Syntax error
     */
    public function testReparseBodyOnContentType()
    {
        $contentType = 'application/x-www-form-urlencoded';
        
        $this->baseRequest->method('getHeaderLine')
            ->will($this->returnCallback(function () use (&$contentType) { return $contentType; }));
        
        $this->baseRequest
            ->method('withHeader')
            ->with('Content-Type', 'application/json; charset=utf-8')
            ->willReturnSelf();

        $body = $this->createMock(Stream::class);
        $body->expects($this->exactly(2))
            ->method('__toString')
            ->willReturn('foo=bar&color=red');

        $request = $this->baseRequest->withBody($body);

        $request->getParsedBody();
        
        $contentType = 'application/json; charset=utf-8';
        $request->withHeader('Content-Type', $contentType)->getParsedBody();
    }

    public function testReparseBodyOnSize()
    {
        $this->setContentType('application/x-www-form-urlencoded');
        
        $body = $this->createMock(Stream::class);
        $body->expects($this->exactly(2))
            ->method('__toString')
            ->willReturnOnConsecutiveCalls('foo=bar', 'foo=bar&color=red');
        
        $body->expects($this->exactly(4))
            ->method('getSize')
            ->willReturnOnConsecutiveCalls(7, 17, 17, 17);
        
        $request = $this->baseRequest->withBody($body);
        
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
        
        // Second call with appended content for body
        $this->assertEquals(['foo' => 'bar', 'color' => 'red'], $request->getParsedBody());
        
        // Third call with no reparse
        $this->assertEquals(['foo' => 'bar', 'color' => 'red'], $request->getParsedBody());
    }
}
