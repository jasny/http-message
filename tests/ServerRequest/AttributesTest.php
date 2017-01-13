<?php

namespace Jasny\HttpMessage\ServerRequest;

use PHPUnit_Framework_TestCase;
use Jasny\TestHelper;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\DerivedAttribute;
use Jasny\HttpMessage\DerivedAttributeInterface;

/**
 * @covers Jasny\HttpMessage\ServerRequest\Attributes
 */
class AttributesTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    /**
     * @var ServerRequest
     */
    protected $baseRequest;
    
    public function setUp()
    {
        $this->baseRequest = new ServerRequest();
    }
    

    /**
     * `ServerRequest::createDerivedAttributes` is protected, but we don't want to execute the derived attributes
     */
    public function testCreateDerivedAttributes()
    {
        $refl = new \ReflectionProperty(ServerRequest::class, 'attributes');
        $refl->setAccessible(true);
        
        $attributes = $refl->getValue($this->baseRequest);
        
        $this->assertArrayHasKey('client_ip', $attributes);
        $this->assertInstanceOf(DerivedAttribute\ClientIp::class, $attributes['client_ip']);
        
        $this->assertArrayHasKey('is_xhr', $attributes);
        $this->assertInstanceOf(DerivedAttribute\IsXhr::class, $attributes['is_xhr']);
        
        $this->assertArrayHasKey('local_referer', $attributes);
        $this->assertInstanceOf(DerivedAttribute\LocalReferer::class, $attributes['local_referer']);
    }

    public function testWithAttribute()
    {
        $request = $this->baseRequest->withAttribute('foo', ['bar', 'zoo']);
        
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['bar', 'zoo'], $request->getAttribute('foo'));
        
        return $request;
    }

    /**
     * @depends testWithAttribute
     */
    public function testWithAttributeOverride(ServerRequest $originalRequest)
    {
        $request = $originalRequest->withAttribute('foo', 'black');
        $this->assertEquals('black', $request->getAttribute('foo'));
    }

    public function testWithAttributeAsCallback()
    {
        $request = $this->baseRequest->withAttribute('foo', function ($arg) use (&$request) {
            $this->assertSame($request, $arg);
            return ['bar', 'zoo'];
        });
        
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertEquals(['bar', 'zoo'], $request->getAttribute('foo'));
    }

    public function testWithAttributeAsObject()
    {
        $attribute = $this->createMock(DerivedAttributeInterface::class);
        $request = $this->baseRequest->withAttribute('foo', $attribute);
        
        $attribute->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($request))
            ->willReturn(['bar', 'zoo']);
        
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertEquals(['bar', 'zoo'], $request->getAttribute('foo'));
    }

    /**
     * @depends testWithAttribute
     */
    public function testWithoutAttribute(ServerRequest $originalRequest)
    {
        $request = $originalRequest->withoutAttribute('foo');
        
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertNull($request->getAttribute('foo'));
    }

    public function testGetAttributes()
    {
        // Remove all attributes, as we don't want to them
        $refl = new \ReflectionProperty(ServerRequest::class, 'attributes');
        $refl->setAccessible(true);
        $refl->setValue($this->baseRequest, []);
        
        $request = $this->baseRequest->withAttribute('foo', function () {
            return ['bar', 'zoo'];
        })->withAttribute('color', 'red');
        
        $this->assertEquals(['foo' => ['bar', 'zoo'], 'color' => 'red'], $request->getAttributes());
    }
}
