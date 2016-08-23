<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject;

use Jasny\HttpMessage\DerivedAttribute\LocalReferer;
use Jasny\HttpMessage\ServerRequest;

/**
 * @covers \Jasny\HttpMessage\DerivedAttribute\LocalReferer
 */
class LocalRefererTest extends PHPUnit_Framework_TestCase
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
     * Referer header not set
     */
    public function testNoReferer()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([['Host', 'www.example.com'], ['Referer', null]]);
        
        $localReferer = new LocalReferer();
        
        $this->assertNull($localReferer($this->request));
    }
    
    /**
     * Local referer with root path
     */
    public function testLocalRefererRoot()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([['Host', 'www.example.com'], ['Referer', 'http://www.example.com/']]);
        
        $localReferer = new LocalReferer();
        
        $this->assertEquals('/', $localReferer($this->request));
    }
    
    /**
     * Local referer with some page
     */
    public function testLocalRefererPage()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([['Host', 'www.example.com'], ['Referer', 'http://www.example.com/pages/foo']]);
        
        $localReferer = new LocalReferer();
        
        $this->assertEquals('/pages/foo', $localReferer($this->request));
    }
    
    /**
     * Host header not set
     */
    public function testNoHost()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([['Host', null], ['Referer', 'http://www.example.com/']]);
        
        $localReferer = new LocalReferer();
        
        $this->assertNull($localReferer($this->request));
    }
    
    /**
     * Host doesn't match referer domain
     */
    public function testNoMatch()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([['Host', 'http://www.example.com/'], ['Referer', 'http://www.example.org/']]);
        
        $localReferer = new LocalReferer();
        
        $this->assertNull($localReferer($this->request));
    }
    
    /**
     * Host doesn't match referer domain through subdomain
     */
    public function testSubdomainsDontMatch()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->willReturnMap([['Host', 'http://www.example.com/'], ['Referer', 'http://foo.example.com/']]);
        
        $localReferer = new LocalReferer();
        
        $this->assertNull($localReferer($this->request));
    }
}
