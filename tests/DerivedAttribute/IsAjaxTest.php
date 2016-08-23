<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject;

use Jasny\HttpMessage\DerivedAttribute\IsAjax;
use Jasny\HttpMessage\ServerRequest;

/**
 * @covers \Jasny\HttpMessage\DerivedAttribute\IsAjax
 */
class IsAjaxTest extends PHPUnit_Framework_TestCase
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
        
        $isAjax = new IsAjax();
        
        $this->assertEquals(false, $isAjax($this->request));
    }
    
    /**
     * An ajax request
     */
    public function testAjax()
    {
        $this->request->expects($this->once())->method('getHeaderLine')
            ->with('X-Requested-With')
            ->willReturn('xmlhttprequest');
        
        $isAjax = new IsAjax();
        
        $this->assertEquals(true, $isAjax($this->request));
    }
    
    /**
     * Not an ajax request, but with an X-Requested-With
     */
    public function testNotAjaxOther()
    {
        $this->request->expects($this->once())->method('getHeaderLine')
            ->with('X-Requested-With')
            ->willReturn('foo-bar');
        
        $isAjax = new IsAjax();
        
        $this->assertEquals(false, $isAjax($this->request));
    }
}
