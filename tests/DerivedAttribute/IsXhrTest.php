<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject;

use Jasny\HttpMessage\DerivedAttribute\IsXhr;
use Jasny\HttpMessage\ServerRequest;

/**
 * @covers \Jasny\HttpMessage\DerivedAttribute\IsXhr
 */
class IsXhrTest extends PHPUnit_Framework_TestCase
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
    public function testNotXhr()
    {
        $this->request->expects($this->once())->method('getHeaderLine')
            ->with('X-Requested-With')
            ->willReturn(null);
        
        $isXhr = new IsXhr();
        
        $this->assertEquals(false, $isXhr($this->request));
    }
    
    /**
     * An ajax request
     */
    public function testXhr()
    {
        $this->request->expects($this->once())->method('getHeaderLine')
            ->with('X-Requested-With')
            ->willReturn('xmlhttprequest');
        
        $isXhr = new IsXhr();
        
        $this->assertEquals(true, $isXhr($this->request));
    }
    
    /**
     * Not an ajax request, but with an X-Requested-With
     */
    public function testNotXhrOther()
    {
        $this->request->expects($this->once())->method('getHeaderLine')
            ->with('X-Requested-With')
            ->willReturn('foo-bar');
        
        $isXhr = new IsXhr();
        
        $this->assertEquals(false, $isXhr($this->request));
    }
}
