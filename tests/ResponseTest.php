<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Jasny\TestHelper;

use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\GlobalResponseHeaders;
use Jasny\HttpMessage\Headers;
use Jasny\HttpMessage\OutputBufferStream;

/**
 * @covers Jasny\HttpMessage\Response
 */
class ResponseTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
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
        $this->setPrivateProperty($this->baseResponse, 'headers', $this->headers);
        
        $this->status = $this->createMock(ResponseStatus::class);
        $this->setPrivateProperty($this->baseResponse, 'status', $this->status);
    }

    
    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Unable to modify a stale response object
     */
    public function testCopyStale()
    {
        $this->setPrivateProperty($this->baseResponse, 'isStale', true);
        $this->baseResponse->withStatus(404);
    }

    
    public function testWithGlobalEnvironment()
    {
        $this->baseResponse = $this->createPartialMock(Response::class,
            ['createGlobalResponseStatus', 'createGlobalResponseHeaders', 'createOutputBufferStream']);
        
        $this->setPrivateProperty($this->baseResponse, 'headers', $this->headers);
        $this->setPrivateProperty($this->baseResponse, 'status', $this->status);
        
        $globalStatus = $this->createMock(GlobalResponseStatus::class);
        $globalHeaders = $this->createMock(GlobalResponseStatus::class);
        $body = $this->createMock(OutputBufferStream::class);
        
        $this->baseResponse->expects($this->once())->method('createGlobalResponseStatus')->willReturn($globalStatus);
        $this->baseResponse->expects($this->once())->method('createGlobalResponseHeaders')->willReturn($globalHeaders);
        $this->baseResponse->expects($this->once())->method('createOutputBufferStream')->willReturn($body);
        
        $body->expects($this->once())->method('useGlobally');
        
        $response = $this->baseResponse->withGlobalEnvironment(true);
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        $this->assertFalse($response->isStale());
        
        $this->assertNull($this->baseResponse->isStale());
    }
    
    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Unable to use a stale response. Did you mean to rivive it?
     */
    public function testWithGlobalEnvironmentStale()
    {
        $this->setPrivateProperty($this->baseResponse, 'isStale', true);
        
        $this->baseResponse->withGlobalEnvironment(true);
    }
    
    public function testWithGlobalEnvironmentGlobal()
    {
        $this->setPrivateProperty($this->baseResponse, 'isStale', false);
        
        $response = $this->baseResponse->withGlobalEnvironment(true);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $this->assertTrue($this->baseResponse->isStale());
    }

    public function withoutGlobalEnvironmentProvider()
    {
        return [[true], [false]];
    }
    
    /**
     * @dataProvider withoutGlobalEnvironmentProvider
     * 
     * @param boolean $bind
     */
    public function testWithoutGlobalEnvironment($bind)
    {
        $response = $this->baseResponse->withGlobalEnvironment($bind);
        
        if ($bind) {
            $response = $response->withoutGlobalEnvironment();
        }
            
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $refl = new \ReflectionProperty($response, 'headers');
        $refl->setAccessible(true);
        $this->assertInstanceof(Headers::class, $refl->getValue($response));
        
        $this->assertInstanceof(OutputBufferStream::class, $response->getBody());
        $this->assertEquals('php://temp', $response->getBody()->getMetadata('uri'));
        
        $this->assertSame($response, $response->withoutGlobalEnvironment());
    }
    
    public function testRevive()
    {
        $this->headers->expects($this->once())->method('getHeaders')->willReturn(['Foo' => ['bar']]);
        
        $this->baseResponse = $this->createPartialMock(Response::class,
            ['createGlobalResponseStatus', 'createGlobalResponseHeaders', 'createOutputBufferStream']);
        
        $this->setPrivateProperty($this->baseResponse, 'headers', $this->headers);
        $this->setPrivateProperty($this->baseResponse, 'status', $this->status);
        
        $globalStatus = $this->createMock(GlobalResponseStatus::class);
        $globalHeaders = $this->createMock(GlobalResponseStatus::class);
        $body = $this->createMock(OutputBufferStream::class);
        
        $this->baseResponse->expects($this->once())->method('createGlobalResponseStatus')->with($this->status)
            ->willReturn($globalStatus);
        
        $this->baseResponse->expects($this->once())->method('createGlobalResponseHeaders')->with(['Foo' => ['bar']])
            ->willReturn($globalHeaders);
        
        $this->baseResponse->expects($this->never())->method('createOutputBufferStream');
        
        $this->setPrivateProperty($this->baseResponse, 'body', $body);
        $body->expects($this->once())->method('useGlobally');
        
        $this->setPrivateProperty($this->baseResponse, 'isStale', true);
        
        $response = $this->baseResponse->revive();
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $this->assertFalse($response->isStale());
    }
    
    public function testReviveNonStale()
    {
        $response = $this->baseResponse->revive();
        
        $this->assertSame($this->baseResponse, $response);
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
    
    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Unable to emit a stale response object
     */
    public function testEmitStale()
    {
        $this->setPrivateProperty($this->baseResponse, 'isStale', true);
        
        $emitter = $this->createMock(EmitterInterface::class);
        
        $this->baseResponse->emit($emitter);
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
