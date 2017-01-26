<?php

namespace Jasny\HttpMessage\Response;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Jasny\TestHelper;

use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\ResponseStatus;
use Jasny\HttpMessage\GlobalResponseStatus;

/**
 * @covers Jasny\HttpMessage\Message\ProtocolVersion
 * @covers Jasny\HttpMessage\Response\ProtocolVersion
 * @covers Jasny\HttpMessage\Response::withProtocolVersion
 */
class ProtocolVersionTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    /**
     * @var Response
     */
    protected $baseResponse;
    
    /**
     * @var ResponseStatus|MockObject
     */
    protected $status;

    public function setUp()
    {
        $this->baseResponse = new Response();
        
        $this->status = $this->createMock(ResponseStatus::class);
        $this->setPrivateProperty($this->baseResponse, 'status', $this->status);
    }
    
    protected function useGlobalResponseStatus()
    {
        $this->status = $this->createMock(GlobalResponseStatus::class);
        $this->setPrivateProperty($this->baseResponse, 'status', $this->status);
    }
    
    public function testGetDefaultProtocolVersion()
    {
        $this->assertSame('1.1', $this->baseResponse->getProtocolVersion());
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
        $response = $this->baseResponse->withProtocolVersion($version);
        
        $this->assertInstanceof(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $this->assertEquals($expect, $response->getProtocolVersion());
    }

    public function testWithProtocolVersionWithGlobal()
    {
        $this->useGlobalResponseStatus();
        $this->status->expects($this->once())->method('withProtocolVersion')->with('1.0');
        
        $this->baseResponse->withProtocolVersion('1.0');
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
}
