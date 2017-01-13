<?php

namespace Jasny\HttpMessage\Response;

use PHPUnit_Framework_TestCase;
use Jasny\TestHelper;

use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\Stream;
use Jasny\HttpMessage\OutputBufferStream;
use Psr\Http\Message\StreamInterface;

/**
 * @covers Jasny\HttpMessage\Message\Body
 * @covers Jasny\HttpMessage\Response\Body
 */
class BodyTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    /**
     * @var Response
     */
    protected $baseResponse;
    
    public function setUp()
    {
        $this->baseResponse = new Response();
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
        $body = $this->createMock(StreamInterface::class);
        
        $response = $this->baseResponse->withBody($body);
        $this->assertSame($body, $response->getBody());
    }
    
    public function testWithBodyWithOutputBufferStream()
    {
        $body = $this->createMock(OutputBufferStream::class);
        $body->expects($this->once())->method('useGlobally');
        
        $this->setPrivateProperty($this->baseResponse, 'isStale', false);
        
        $response = $this->baseResponse->withBody($body);
        $this->assertSame($body, $response->getBody());
    }
}
