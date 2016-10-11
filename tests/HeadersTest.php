<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use Jasny\HttpMessage\Tests\AssertLastError;
use Jasny\HttpMessage\Headers;

class HeadersTest extends PHPUnit_Framework_TestCase
{
    use AssertLastError;
    
    /**
     * @var ServerRequest
     */
    protected $headers;

    public function setUp()
    {
        $this->headers = new Headers();
    }

    public function testDefaultEmptyHeaders()
    {
        $headers = $this->headers->getHeaders();
        $this->assertSame([], $headers);
    }

    public function testWithHeader()
    {
        $request = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        
        $this->assertInstanceof(Headers::class, $request);
        $this->assertNotSame($this->headers, $request);
        
        $this->assertEquals(['Foo-Zoo' => ['red & blue']], $request->getHeaders());
        
        $this->assertTrue($request->hasHeader('Foo-Zoo'));
        
        return $request;
    }

    public function testWithHeaderCaseSensetive()
    {
        $request = $this->headers->withHeader('FOO-ZoO', 'red & blue');
        
        $this->assertInstanceof(Headers::class, $request);
        $this->assertNotSame($this->headers, $request);
        
        $this->assertEquals(['FOO-ZoO' => ['red & blue']], $request->getHeaders());
        
        return $request;
    }

    /**
     * @depends testWithHeader
     */
    public function testHasHeaderCaseSensetive(Headers $request)
    {
        $this->assertTrue($request->hasHeader('Foo-Zoo'));
    }

    /**
     * @depends testWithHeader
     */
    public function testWithHeaderAddAnother(Headers $origRequest)
    {
        $request = $origRequest->withHeader('QUX', 'white');
        $this->assertEquals(['Foo-Zoo' => ['red & blue'], 'QUX' => ['white']], $request->getHeaders());
        
        return $request;
    }

    /**
     * @depends testWithHeader
     */
    public function testWithHeaderOverwrite(Headers $origRequest)
    {
        $request = $origRequest->withHeader('foo-zoo', 'silver & gold');
        $this->assertEquals(['foo-zoo' => ['silver & gold']], $request->getHeaders());
    }

    /**
     * @depends testWithHeader
     */
    public function testWithAddedHeader(Headers $origRequest)
    {
        $this->assertTrue($origRequest->hasHeader('Foo-Zoo'));
        $request = $origRequest->withAddedHeader('foo-zoo', 'silver & gold');
        
        $this->assertInstanceof(Headers::class, $request);
        $this->assertNotSame($this->headers, $request);
        
        $this->assertEquals(['Foo-Zoo' => ['red & blue', 'silver & gold']], $request->getHeaders());
    }

    public function testWithAddedHeaderNew()
    {
        $request = $this->headers->withAddedHeader('Qux', 'white');
        
        $this->assertInstanceof(Headers::class, $request);
        $this->assertNotSame($this->headers, $request);
        
        $this->assertEquals(['Qux' => ['white']], $request->getHeaders());
    }

    /**
     * @depends testWithHeader
     */
    public function testWithoutHeaderNotExists(Headers $request)
    {
        $requestDeleted = $request->withoutHeader('not-exists');
        $this->assertSame($request, $requestDeleted);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Header name must be a string
     */
    public function testWithHeaderArrayAsName()
    {
        $this->headers->withHeader(['foo' => 'bar'], 'zoo');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid header name 'foo bar'
     */
    public function testWithHeaderInvalidName()
    {
        $this->headers->withHeader('foo bar', 'zoo');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Header value should be a string or an array of strings
     */
    public function testWithHeaderArrayAsValue()
    {
        $this->headers->withHeader('foo', ['bar', ['zoo', 'woo']]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Header name must be a string
     */
    public function testWithAddedHeaderArrayAsName()
    {
        $this->headers->withAddedHeader(['foo' => 'bar'], 'zoo');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid header name 'foo bar'
     */
    public function testWithAddedHeaderInvalidName()
    {
        $this->headers->withAddedHeader('foo bar', 'zoo');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Header name must be a string
     */
    public function testWithoutHeaderArrayAsName()
    {
        $request = $this->headers->withoutHeader(['foo', 'bar']);
        $this->assertSame($this->headers, $request);
    }

    /**
     * @depends testWithHeader
     */
    public function testGetHeader(Headers $request)
    {
        $this->assertEquals(['red & blue'], $request->getHeader('Foo-Zoo'));
    }

    /**
     * @depends testWithHeader
     */
    public function testGetHeaderCaseInsensetive(Headers $request)
    {
        $this->assertEquals(['red & blue'], $request->getHeader('FOO-ZOO'));
    }

    /**
     * @depends testWithHeader
     */
    public function testGetHeaderLineOneValue(Headers $request)
    {
        $this->assertEquals('red & blue', $request->getHeaderLine('FoO-zoo'));
    }

    /**
     * @depends testWithHeader
     */
    public function testGetHeaderLineMultipleValue(Headers $origRequest)
    {
        $request = $origRequest->withAddedHeader('foo-zoo', 'silver & gold');
        
        $this->assertEquals('red & blue, silver & gold', $request->getHeaderLine('FoO-zoo'));
    }

    public function testGetHeaderLineNotExists()
    {
        $this->assertEquals('', $this->headers->getHeaderLine('NotExists'));
    }
}
