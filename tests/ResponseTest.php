<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Jasny\TestHelper;

use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\GlobalResponseHeaders;
use Jasny\HttpMessage\Headers;

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
    
    public function testWithGlobalEnvironment()
    {
        $response = $this->baseResponse->withGlobalEnvironment(true);
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $this->assertNull($this->baseResponse->isStale());
        $this->assertFalse($response->isStale());
        
        $refl = new \ReflectionProperty($response, 'headers');
        $refl->setAccessible(true);
        $this->assertInstanceof(GlobalResponseHeaders::class, $refl->getValue($response));
        
        $this->assertInstanceof(OutputBufferStream::class, $response->getBody());
        $this->assertEquals('php://temp', $response->getBody()->getMetadata('uri'));
        
        $this->assertSame($response, $response->withGlobalEnvironment());
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
