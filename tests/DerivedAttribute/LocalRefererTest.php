<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject;

use Jasny\HttpMessage\DerivedAttribute\LocalReferer;
use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\Uri;

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
            ->setMethods(['getHeaderLine', 'getUri'])
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();
        
        $uri = $this->getMockBuilder(Uri::class)
            ->setMethods(['getScheme', 'getHost', 'getPort'])
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();
        
        $uri->expects($this->any())->method('getScheme')->willReturn('http');
        $uri->expects($this->any())->method('getHost')->willReturn('www.example.com');
        $uri->expects($this->any())->method('getPort')->willReturn(null);
        
        $this->request->expects($this->any())->method('getUri')->willReturn($uri);
    }

    /**
     * Referer header not set
     */
    public function testNoReferer()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->with('Referer')->willReturn(null);
        
        $localReferer = new LocalReferer();
        
        $this->assertNull($localReferer($this->request));
    }
    
    /**
     * Local referer with root path
     */
    public function testLocalRefererRoot()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->with('Referer')->willReturn('http://www.example.com/');
        
        $localReferer = new LocalReferer();
        
        $this->assertEquals('/', $localReferer($this->request));
    }
    
    /**
     * Local referer with some page
     */
    public function testLocalRefererPage()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->with('Referer')->willReturn('http://www.example.com/pages/foo');
        
        $localReferer = new LocalReferer();
        
        $this->assertEquals('/pages/foo', $localReferer($this->request));
    }
    
    /**
     * Host doesn't match referer domain
     */
    public function testNoMatch()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->with('Referer')->willReturn('http://www.example.org/');
        
        $localReferer = new LocalReferer();
        
        $this->assertNull($localReferer($this->request));
    }
    
    /**
     * Host doesn't match referer domain through subdomain
     */
    public function testSubdomainsDontMatch()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->with('Referer')->willReturn('http://foo.example.com/');
        
        $localReferer = new LocalReferer();
        
        $this->assertNull($localReferer($this->request));
    }
    
    /**
     * Port doesn't match referer port
     */
    public function testPortsDontMatch()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->with('Referer')->willReturn('http://www.example.com:8080/');
        
        $localReferer = new LocalReferer();
        
        $this->assertNull($localReferer($this->request));
    }
    
    /**
     * Port doesn't match referer port
     */
    public function testDontCheckPort()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->with('Referer')->willReturn('http://www.example.com:8080/');
        
        $localReferer = new LocalReferer(['checkPort' => false]);
        
        $this->assertEquals('/', $localReferer($this->request));
    }
    
    /**
     * Scheme doesn't match referer
     */
    public function testSchemesDontMatch()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->with('Referer')->willReturn('https://www.example.com/');
        
        $localReferer = new LocalReferer();
        
        $this->assertNull($localReferer($this->request));
    }
    
    /**
     * Don't check the scheme
     */
    public function testDontCheckScheme()
    {
        $this->request->expects($this->any())->method('getHeaderLine')
            ->with('Referer')->willReturn('https://www.example.com/');
        
        $localReferer = new LocalReferer(['checkScheme' => false]);
        
        $this->assertEquals('/', $localReferer($this->request));
    }
}
