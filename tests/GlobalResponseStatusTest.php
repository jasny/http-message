<?php

namespace Jasny\HttpMessage;

use Jasny\HttpMessage\GlobalResponseStatus;
use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * @covers Jasny\HttpMessage\GlobalResponseStatus
 */
class GlobalResponseStatusTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ResponseStatus|MockObject
     */
    protected $baseStatus;
    
    public function setUp()
    {
        $this->baseStatus = $this->getMockBuilder(GlobalResponseStatus::class)
            ->setMethods(['header', 'headersSent', 'httpResponseCode'])
            ->getMock();
        
        $this->baseStatus->method('headersSent')->willReturn(false);
    }
    
    
    public function testStatusCodeDefaults()
    {
        $this->assertSame(200, $this->baseStatus->getStatusCode());
        $this->assertSame('OK', $this->baseStatus->getReasonPhrase());
    }

    
    public function statusCodeProvider()
    {
        return [
            [404, null, 'Not Found'],
            [404, 'Some unique status', 'Some unique status'],
            [999, null, ''],
        ];
    }

    /**
     * @dataProvider statusCodeProvider
     * 
     * @param int    $status
     * @param string $phrase
     * @param string $expectPhrase
     */
    public function testWithStatus($status, $phrase, $expectPhrase)
    {
        $globalStatus = 200;
        
        $this->baseStatus->expects($this->any())->method('httpResponseCode')->willReturnReference($globalStatus);
        $this->baseStatus->expects($this->once())->method('header')
            ->with("HTTP/1.1 {$status} {$expectPhrase}")
            ->will($this->returnCallback(function() use (&$globalStatus, $status) {
                $globalStatus = $status;
            }));
        
        $responseStatus = $this->baseStatus->withStatus($status, $phrase);

        $this->assertSame($this->baseStatus, $responseStatus);

        $this->assertSame($status, $responseStatus->getStatusCode());
        $this->assertSame($expectPhrase, $responseStatus->getReasonPhrase());
    }
    
    public function testGetStatus()
    {
        $this->baseStatus->expects($this->any())->method('httpResponseCode')->willReturn(400);
        
        $this->assertSame(400, $this->baseStatus->getStatusCode());
        $this->assertSame('Bad Request', $this->baseStatus->getReasonPhrase());
    }
    
    
    public function testWithProtocolVersion()
    {
        $this->baseStatus->expects($this->exactly(2))->method('header')->withConsecutive(
            ["HTTP/2 200 OK"],
            ["HTTP/2 404 Not Found"]
        );
        
        $responseStatus = $this->baseStatus->withProtocolVersion('2');
        $this->assertSame($this->baseStatus, $responseStatus);

        $this->baseStatus->withStatus(404, 'Not Found');
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithProtocolVerionsInvalidArgument()
    {
        $this->baseStatus->withProtocolVersion(0);
    }
}
