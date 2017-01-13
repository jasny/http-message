<?php

namespace Jasny\HttpMessage\Response;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Jasny\TestHelper;

use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\ResponseStatus;
use Jasny\HttpMessage\GlobalResponseStatus;

/**
 * @covers Jasny\HttpMessage\Response\Status
 */
class StatusTest extends PHPUnit_Framework_TestCase
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
    

    public function testGetDefaultStatus()
    {
        $response = new Response();
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
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
    
    
    public function statusProvider()
    {
        return [
            [500, 'Some Reason'],
            [200, 'All good']
        ];
    }
    
    /**
     * @dataProvider statusProvider
     * 
     * @param int    $code
     * @param string $phrase
     */
    public function testWithStatus($code, $phrase)
    {
        $newStatus = $this->createMock(ResponseStatus::class);
        
        $this->status->method('getStatusCode')->willReturn(200);
        $this->status->method('getReasonPhrase')->willReturn('OK');
        
        $this->status->expects($this->once())->method('withStatus')->with($code, $phrase)->willReturn($newStatus);
        
        $response = $this->baseResponse->withStatus($code, $phrase);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        
        $this->assertAttributeSame($newStatus, 'status', $response);
    }
    
    public function statusNoChangeProvider()
    {
        return [
            [200],
            [200, 'OK'],
            ['200', 'OK']
        ];
    }
    
    /**
     * @dataProvider statusNoChangeProvider
     * 
     * @param int    $code
     * @param string $phrase
     */
    public function testWithStatusNoChange($code, $phrase = '')
    {
        $this->status->method('getStatusCode')->willReturn(200);
        $this->status->method('getReasonPhrase')->willReturn('OK');
        
        $this->status->expects($this->never())->method('withStatus');
        
        $response = $this->baseResponse->withStatus($code, $phrase);
        
        $this->assertSame($this->baseResponse, $response);
    }
   
    public function testWithStatusGlobal()
    {
        $this->status = $this->createMock(GlobalResponseStatus::class);
        $this->setPrivateProperty($this->baseResponse, 'status', $this->status);
        
        $this->status->method('getStatusCode')->willReturn(200);
        $this->status->method('getReasonPhrase')->willReturn('OK');
        
        $this->status->expects($this->once())->method('withStatus')->with(404, 'Not Found')->willReturnSelf();
        
        $this->setPrivateProperty($this->baseResponse, 'isStale', false);
        
        $response = $this->baseResponse->withStatus(404, 'Not Found');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotSame($this->baseResponse, $response);
        $this->assertAttributeSame($this->status, 'status', $response);
        $this->assertFalse($response->isStale());
        
        $this->assertTrue($this->baseResponse->isStale());
    }
}
