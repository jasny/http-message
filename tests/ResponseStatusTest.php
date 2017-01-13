<?php

namespace Jasny\HttpMessage;

use Jasny\HttpMessage\ResponseStatus;
use PHPUnit_Framework_TestCase;

/**
 * @covers Jasny\HttpMessage\ResponseStatus
 */
class ResponseStatusTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ResponseStatus
     */
    protected $baseStatus;
    
    public function setUp()
    {
        $this->baseStatus = new ResponseStatus();
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
    public function testConstruct($status, $phrase, $expectPhrase)
    {
        $responseStatus = new ResponseStatus($status, $phrase);
        
        $this->assertSame($status, $responseStatus->getStatusCode());
        $this->assertSame($expectPhrase, $responseStatus->getReasonPhrase());
    }    

    /**
     * @dataProvider statusCodeProvider
     * 
     * @param int    $status
     * @param string $phrase
     * @param string $expectPhrase
     */
    public function testConstructWithResposnes($status, $phrase, $expectPhrase)
    {
        $original = new ResponseStatus($status, $phrase);
        $responseStatus = new ResponseStatus($original);
        
        $this->assertSame($status, $responseStatus->getStatusCode());
        $this->assertSame($expectPhrase, $responseStatus->getReasonPhrase());
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
        $responseStatus = $this->baseStatus->withStatus($status, $phrase);
        
        $this->assertInstanceOf(ResponseStatus::class, $responseStatus);
        $this->assertNotSame($this->baseStatus, $responseStatus);
        
        $this->assertSame($status, $responseStatus->getStatusCode());
        $this->assertSame($expectPhrase, $responseStatus->getReasonPhrase());
    }
    
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidTypeStatusCode()
    {
        $this->baseStatus->withStatus(1020.20);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidValueStatusCode()
    {
        $this->baseStatus->withStatus(1020);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidValueStatusPhrase()
    {
        $this->baseStatus->withStatus(200, ['foo', 'bar']);
    }
    
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructInvalidTypeProtocolVersion()
    {
        new ResponseStatus(['woo']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructInvalidTypeStatusCode()
    {
        new ResponseStatus(1020.20);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructInvalidValueStatusCode()
    {
        new ResponseStatus(1020);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructInvalidValueStatusPhrase()
    {
        new ResponseStatus(200, ['foo', 'bar']);
    }
}
