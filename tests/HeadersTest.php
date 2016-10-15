<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use Jasny\HttpMessage\Headers;

class HeadersTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Headers
     */
    protected $headers;

    public function setUp()
    {
        $this->headers = new Headers();
    }

    
    public function testConstruct()
    {
        $headers = new Headers([
            'Foo' => 'bar',
            'Colors' => ['red', 'blue', 'green'],
        ]);
        
        $this->assertEquals([
            'Foo' => ['bar'],
            'Colors' => ['red', 'blue', 'green'],
        ], $headers->getHeaders());
    }
    
    
    public function testDefaultEmptyHeaders()
    {
        $headers = $this->headers->getHeaders();
        $this->assertSame([], $headers);
    }

    public function testWithHeader()
    {
        $headers = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        
        $this->assertInstanceof(Headers::class, $headers);
        $this->assertNotSame($this->headers, $headers);
        
        $this->assertEquals(['Foo-Zoo' => ['red & blue']], $headers->getHeaders());
        
        $this->assertTrue($headers->hasHeader('Foo-Zoo'));
        
        return $headers;
    }

    public function testWithHeaderCaseSensetive()
    {
        $headers = $this->headers->withHeader('FOO-ZoO', 'red & blue');
        
        $this->assertInstanceof(Headers::class, $headers);
        $this->assertNotSame($this->headers, $headers);
        
        $this->assertEquals(['FOO-ZoO' => ['red & blue']], $headers->getHeaders());
        
        return $headers;
    }

    /**
     * @depends testWithHeader
     */
    public function testHasHeaderCaseSensetive(Headers $headers)
    {
        $this->assertTrue($headers->hasHeader('Foo-Zoo'));
    }

    /**
     * @depends testWithHeader
     */
    public function testWithHeaderAddAnother(Headers $origRequest)
    {
        $headers = $origRequest->withHeader('QUX', 'white');
        $this->assertEquals(['Foo-Zoo' => ['red & blue'], 'QUX' => ['white']], $headers->getHeaders());
        
        return $headers;
    }

    /**
     * @depends testWithHeader
     */
    public function testWithHeaderOverwrite(Headers $origRequest)
    {
        $headers = $origRequest->withHeader('foo-zoo', 'silver & gold');
        $this->assertEquals(['foo-zoo' => ['silver & gold']], $headers->getHeaders());
    }

    /**
     * @depends testWithHeader
     */
    public function testWithAddedHeader(Headers $origRequest)
    {
        $this->assertTrue($origRequest->hasHeader('Foo-Zoo'));
        $headers = $origRequest->withAddedHeader('foo-zoo', 'silver & gold');
        
        $this->assertInstanceof(Headers::class, $headers);
        $this->assertNotSame($this->headers, $headers);
        
        $this->assertEquals(['Foo-Zoo' => ['red & blue', 'silver & gold']], $headers->getHeaders());
    }

    public function testWithAddedHeaderNew()
    {
        $headers = $this->headers->withAddedHeader('Qux', 'white');
        
        $this->assertInstanceof(Headers::class, $headers);
        $this->assertNotSame($this->headers, $headers);
        
        $this->assertEquals(['Qux' => ['white']], $headers->getHeaders());
    }

    /**
     * @depends testWithHeader
     */
    public function testWithoutHeader(Headers $headers)
    {
        $headersDeleted = $headers->withoutHeader('Foo-Zoo');
        
        $this->assertInstanceOf(Headers::class, $headersDeleted);
        $this->assertNotSame($headers, $headersDeleted);
        
        $this->assertEquals([], $headersDeleted->getHeaders());
    }

    /**
     * @depends testWithHeader
     */
    public function testWithoutHeaderNotExists(Headers $headers)
    {
        $headersDeleted = $headers->withoutHeader('not-exists');
        $this->assertSame($headers, $headersDeleted);
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
        $headers = $this->headers->withoutHeader(['foo', 'bar']);
        $this->assertSame($this->headers, $headers);
    }

    /**
     * @depends testWithHeader
     */
    public function testGetHeader(Headers $headers)
    {
        $this->assertEquals(['red & blue'], $headers->getHeader('Foo-Zoo'));
    }

    /**
     * @depends testWithHeader
     */
    public function testGetHeaderCaseInsensetive(Headers $headers)
    {
        $this->assertEquals(['red & blue'], $headers->getHeader('FOO-ZOO'));
    }

    /**
     * @depends testWithHeader
     */
    public function testGetHeaderLineOneValue(Headers $headers)
    {
        $this->assertEquals('red & blue', $headers->getHeaderLine('FoO-zoo'));
    }

    /**
     * @depends testWithHeader
     */
    public function testGetHeaderLineMultipleValue(Headers $origRequest)
    {
        $headers = $origRequest->withAddedHeader('foo-zoo', 'silver & gold');
        
        $this->assertEquals('red & blue, silver & gold', $headers->getHeaderLine('FoO-zoo'));
    }

    public function testGetHeaderLineNotExists()
    {
        $this->assertEquals('', $this->headers->getHeaderLine('NotExists'));
    }
}
