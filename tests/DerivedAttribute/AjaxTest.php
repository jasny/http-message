<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject;

use Jasny\HttpMessage\DerivedAttribute\Ajax;
use Jasny\HttpMessage\ServerRequest;

/**
 * @covers \Jasny\HttpMessage\DerivedAttribute\Ajax
 */
class AjaxTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequest|PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;
    
    
    /**
     * Run before each test
     */
    protected function setUp()
    {
        $this->request = $this->getMockBuilder(ServerRequest::class)
            ->setMethods(['getHeaderLine'])
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();
    }
    
    
    /**
     * Not an ajax request
     */
    public function testNotAjax()
    {
        $this->request->expects($this->once())->method('getHeaderLine')
            ->with('X-Requested-With')
            ->willReturn(null);
        
        $clientIp = new Ajax();
        
        $this->assertEquals(false, $clientIp($this->request));
    }
    
    /**
     * An ajax request
     */
    public function testAjax()
    {
        $this->request->expects($this->once())->method('getHeaderLine')
            ->with('X-Requested-With')
            ->willReturn('xmlhttprequest');
        
        $clientIp = new Ajax();
        
        $this->assertEquals(true, $clientIp($this->request));
    }
    
    /**
     * Not an ajax request, but with an X-Requested-With
     */
    public function testNotAjaxOther()
    {
        $this->request->expects($this->once())->method('getHeaderLine')
            ->with('X-Requested-With')
            ->willReturn('foo-bar');
        
        $clientIp = new Ajax();
        
        $this->assertEquals(false, $clientIp($this->request));
    }
}
