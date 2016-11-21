<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Jasny\HttpMessage\Emitter;
use Jasny\HttpMessage\Response;

/**
 * @covers Jasny\HttpMessage\Emitter
 * @covers Jasny\HttpMessage\Wrap\Headers
 */
class EmitterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Response|MockObject
     */
    protected $response;    
    
    /**
     * @var Emitter|MockObject
     */
    protected $emitter;    
    
    public function setUp()
    {
        $this->response = $this->createMock(ResponseInterface::class);
        $this->emitter = $this->createPartialMock(Emitter::class, ['header', 'headerRemove', 'headersSent',
            'headersList', 'httpResponseCode', 'createOutputStream']);
    }
    
    public function statusProvider()
    {
        return [
            ['2', 400, 'No Good', "HTTP/2 400 No Good"],
            [null, null, null, "HTTP/1.1 200 OK"],
            ['1.0', 500, null, "HTTP/1.0 500 Internal Server Error"]
        ];
    }
    
    /**
     * @dataProvider statusProvider
     * 
     * @param string $protocolVersion
     * @param int    $code
     * @param string $phrase
     * @param string $expect
     */
    public function testEmitStatus($protocolVersion, $code, $phrase, $expect)
    {
        $this->response->expects($this->once())->method('getProtocolVersion')->willReturn($protocolVersion);
        $this->response->expects($this->once())->method('getStatusCode')->willReturn($code);
        $this->response->expects($this->once())->method('getReasonPhrase')->willReturn($phrase);
        
        $this->emitter->expects($this->once())->method('header')->with($expect, true);
        
        $this->emitter->emitStatus($this->response);
    }
    
    public function testEmitHeaders()
    {
        $this->response->expects($this->once())->method('getHeaders')->willReturn([
            'Content-Type' => ['text/plain'],
            'Location' => 'http://www.example.com/', // Be lenient 
            'X-Animal' => ['mouse', 'bear', 'cow']
        ]);
        
        $this->emitter->expects($this->exactly(5))->method('header')->withConsecutive(
            ['Content-Type: text/plain', true],
            ['Location: http://www.example.com/', true],
            ['X-Animal: mouse', true],
            ['X-Animal: bear', false],
            ['X-Animal: cow', false]
        );
        
        $this->emitter->emitHeaders($this->response);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Headers already sent in foo.php on line 42
     */
    public function testAssertHeadersSent()
    {
        $this->emitter->expects($this->once())->method('headersSent')->willReturn([true, 'foo.php', 42]);
        $this->emitter->emitStatus($this->response);
    }
    
    public function testEmitBody()
    {
        $input = fopen('data://text/plain,hello world', 'r');
        $output = fopen('php://temp', 'r+');
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('detach')->willReturn($input);
        
        $this->response->expects($this->once())->method('getBody')->willReturn($stream);
        
        $this->emitter->expects($this->once())->method('createOutputStream')->willReturn($output);
        
        $this->emitter->emitBody($this->response);
        
        fseek($output, 0);
        $content = fread($output, 256);
        
        $this->assertEquals('hello world', $content);
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to open output stream
     */
    public function testEmitBodyFailure()
    {
        $this->emitter->expects($this->once())->method('createOutputStream')->willReturn(false);
        $this->emitter->emitBody($this->response);
    }
    
    public function testEmit()
    {
        $emitter = $this->getMockBuilder(Emitter::class)->setMethodsExcept(['emit'])->getMock();
        
        $emitter->expects($this->once())->method('emitStatus')->with($this->response);
        $emitter->expects($this->once())->method('emitHeaders')->with($this->response);
        $emitter->expects($this->once())->method('emitBody')->with($this->response);
        
        $emitter->emit($this->response);
    }
    
    public function testEmitResponse()
    {
        $emitter = $this->getMockBuilder(Emitter::class)->setMethodsExcept(['emit'])->getMock();
        
        // For some reason there is an error when mocking withProtocolVersion
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['withProtocolVersion'])
            ->getMock();
        
        $response->expects($this->once())->method('emit')->with($emitter);
        
        $emitter->emit($response);
    }
    
    public function testInvoke()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        
        $emitter = $this->getMockBuilder(Emitter::class)->setMethodsExcept(['__invoke'])->getMock();
        $emitter->expects($this->once())->method('emit')->with($this->response);
        
        $emitter($request, $this->response);
    }
    
    public function testInvokeWithNext()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        
        $emitter = $this->getMockBuilder(Emitter::class)->setMethodsExcept(['__invoke'])->getMock();
        $emitter->expects($this->once())->method('emit')->with($this->response);
        
        $next = $this->createPartialMock(\stdClass::class, ['__invoke']);
        $next->expects($this->once())->method('__invoke')->with($request, $this->response);
        
        $emitter($request, $this->response, $next);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvokeInvalidNext()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        
        $emitter = $this->getMockBuilder(Emitter::class)->setMethodsExcept(['__invoke'])->getMock();
        $emitter->expects($this->never())->method('emit')->with($this->response);
        
        $emitter($request, $this->response, 'not callable');
    }
}
