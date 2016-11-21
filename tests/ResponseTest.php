<?php

namespace Jasny\HttpMessage;

use Jasny\HttpMessage\Tests\AssertLastError;
use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\ResponseHeaders;
use Jasny\HttpMessage\Headers;
use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

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
     * @var Headers|MockObject
     */
    protected $headers;
    
    /**
     * @var ResponseStatus|MockObject
     */
    protected $status;

    public function setUp()
    {
        $this->baseResponse = new Response();
        $this->headers = $this->createMock(Headers::class);
        $this->status = $this->createMock(ResponseStatus::class);
        
        $reflHeaders = new \ReflectionProperty(Response::class, 'headers');
        $reflHeaders->setAccessible(true);
        $reflHeaders->setValue($this->baseResponse, $this->headers);
        
        $reflStatus = new \ReflectionProperty(Response::class, 'status');
        $reflStatus->setAccessible(true);
        $reflStatus->setValue($this->baseResponse, $this->status);
    }
    
    public function testWithGlobalEnvironment()
    {
        $response = $this->baseResponse->withGlobalEnvironment();
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $this->assertTrue($this->baseResponse->isStale());
        
        $refl = new \ReflectionProperty($response, 'headers');
        $refl->setAccessible(true);
        $this->assertInstanceof(ResponseHeaders::class, $refl->getValue($response));
        
        $this->assertInstanceof(OutputBufferStream::class, $response->getBody());
        $this->assertEquals('php://output', $response->getBody()->getMetadata('uri'));
    }

    public function testWithoutGlobalEnvironment()
    {
        $response = $this->baseResponse->withGlobalEnvironment()->withoutGlobalEnvironment();
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $refl = new \ReflectionProperty($response, 'headers');
        $refl->setAccessible(true);
        $this->assertInstanceof(Headers::class, $refl->getValue($response));
        
        $this->assertInstanceof(OutputBufferStream::class, $response->getBody());
        $this->assertEquals('php://temp', $response->getBody()->getMetadata('uri'));
    }
    
    
    public function protocolVersionProvider()
    {
        return [
            ['2', '2'],
            ['1.1', '1.1'],
            ['1.0', '1.0'],
            [2.0, '2'],
            [1, '1.0']
        ];
    }

    /**
     * @dataProvider protocolVersionProvider
     * 
     * @param mixed  $version
     * @param string $expect
     */
    public function testWithProtocolVersion($version, $expect)
    {
        $this->status->expects($this->once())->method('withProtocolVersion')->with($expect)->willReturnSelf();
        
        $response = $this->baseResponse->withProtocolVersion($version);
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $this->assertEquals($expect, $response->getProtocolVersion());
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


    public function testGetDefaultStatusCode()
    {
        $response = new Response();
        $this->assertSame(200, $response->getStatusCode());
    }
    
    public function testGetStatusCode()
    {
        $this->status->expects($this->once())->method('getStatusCode')->willReturn(404);
        $this->assertSame(404, $this->baseResponse->getStatusCode());
    }
    
    public function testGetReasonPhrase()
    {
        $this->status->expects($this->once())->method('getReasonPhrase')->willReturn('Not Found');
        $this->assertSame('Not Found', $this->baseResponse->getReasonPhrase());
    }
    
    public function testWithStatus()
    {
        $this->status->expects($this->once())->method('withStatus')->with(500, 'Some Reason');
        
        $response = $this->baseResponse->withStatus(500, 'Some Reason');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
    }
    

    public function testGetDefaultHeaders()
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
    
    
    public function emitProvider()
    {
        return [
            [],
            ['getStatusCode', ['emitStatus' => $this->never()]],
            ['getHeaders', ['emitHeaders' => $this->never()]],
            ['getBody', ['emitBody' => $this->never()]]
        ];
    }
    
    /**
     * @dataProvider emitProvider
     * 
     * @param string $mockMethod
     * @param array  $expect
     */
    public function testEmit($mockMethod = null, array $expect = [])
    {
        $expect += ['emitStatus' => $this->once(), 'emitHeaders' => $this->once(), 'emitBody' => $this->once()];
        
        $response = $this->createPartialMock(Response::class, (array)$mockMethod);
        
        // Make sure these things are initialized
        $response->getStatusCode();
        $response->getHeaders();
        $response->getBody();
        
        $emitter = $this->createMock(EmitterInterface::class);
        
        $emitter->expects($expect['emitStatus'])->method('emitStatus')->with($response);
        $emitter->expects($expect['emitHeaders'])->method('emitHeaders')->with($response);
        $emitter->expects($expect['emitBody'])->method('emitBody')->with($response);
        
        $response->emit($emitter);
    }
    
    public function testCreateEmitter()
    {
        $refl = new \ReflectionMethod($this->baseResponse, 'createEmitter');
        $refl->setAccessible(true);
        $emitter = $refl->invoke($this->baseResponse);
        
        $this->assertInstanceOf(Emitter::class, $emitter);
    }
    
    public function testEmitCreateEmitter()
    {
        $response = $this->createPartialMock(Response::class, ['createEmitter']);
        
        // Make sure these things are initialized
        $response->getStatusCode();
        $response->getHeaders();
        $response->getBody();
        
        $emitter = $this->createMock(EmitterInterface::class);
        $response->expects($this->once())->method('createEmitter')->willReturn($emitter);
        
        $emitter->expects($this->once())->method('emitStatus')->with($response);
        $emitter->expects($this->once())->method('emitHeaders')->with($response);
        $emitter->expects($this->once())->method('emitBody')->with($response);
        
        $response->emit();
    }
}
