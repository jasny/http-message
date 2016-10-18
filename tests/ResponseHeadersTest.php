<?php

namespace Jasny\HttpMessage;

use Jasny\HttpMessage\Tests\AssertLastError;
use PHPUnit_Framework_TestCase;

/**
 * @covers Jasny\HttpMessage\ResponseHeaders
 * @runTestsInSeparateProcesses
 */
class ResponseHeadersTest extends PHPUnit_Framework_TestCase
{
    use AssertLastError;
    
    /**
     * @var ResponseHeaders
     */
    protected $headers;

    public function setUp()
    {
        $this->headers = new ResponseHeaders();
    }

    public function testConstruct()
    {
        
         $headers = new ResponseHeaders([
         'Foo' => 'bar', 
         'Colors' => ['red', 'blue', 'green']
         ]);
         
         $this->assertEquals([
         'Foo' => ['bar'], 
         'Colors' => ['red', 'blue', 'green']
         ], $headers->getHeaders());
    }

    public function testDefaultEmptyHeaders()
    {
        $headers = $this->headers->getHeaders();
        $this->assertSame([], $headers);
    }

    public function testWithHeader()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        
        $this->assertInstanceof(ResponseHeaders::class, $header);
        $this->assertNotSame($this->headers, $header);
        return $header;
    }

    public function testGetHeaders()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        $this->assertEquals(['Foo-Zoo' => ['red & blue']], $header->getHeaders());
    }

    public function testGetHeader()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        $this->assertEquals(['red & blue'], $header->getHeader('Foo-Zoo'));
    }

    public function testGetHeaderCaseInsensetive()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        $this->assertEquals(['red & blue'], $header->getHeader('FOO-ZOO'));
    }

    public function testHasHeaderCaseSensetive()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        $this->assertTrue($header->hasHeader('Foo-Zoo'));
    }

    public function testHasHeaderNotExists()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        $this->assertFalse($header->hasHeader('non-exists'));
    }

    public function testWithHeaderOverwrite()
    {
        $header = $this->headers
            ->withHeader('Foo-Zoo', 'red & blue')
            ->withHeader('foo-zoo', 'silver & gold');
        $this->assertEquals(['foo-zoo' => ['silver & gold']], $header->getHeaders());
    }

    public function testWithAddedHeader()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue')->withAddedHeader('foo-zoo', 'silver & gold');
        
        $this->assertInstanceof(ResponseHeaders::class, $header);
        $this->assertNotSame($this->headers, $header);
        
        $this->assertEquals(['Foo-Zoo' => ['red & blue', 'silver & gold']], $header->getHeaders());
    }

    public function testWithHeaderAddAnother()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue')
                                ->withHeader('QUX', 'white');
        $this->assertEquals(['Foo-Zoo' => ['red & blue'], 'QUX' => ['white']], $header->getHeaders());
    }

    public function testWithAddedHeaderNew()
    {
        $header = $this->headers->withAddedHeader('Qux', 'white');
        
        $this->assertInstanceof(ResponseHeaders::class, $header);
        $this->assertNotSame($this->headers, $header);
        
        $this->assertEquals(['Qux' => ['white']], $header->getHeaders());
    }

    public function testWithoutHeaderNotExists()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        $headerDeleted = $header->withoutHeader('not-exists');
        $this->assertSame($header, $headerDeleted);
    }

    public function testGetHeaderLineOneValue()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        $this->assertEquals('red & blue', $header->getHeaderLine('FoO-zoo'));
    }

    public function testGetHeaderLineMultipleValue()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue')
                                    ->withAddedHeader('foo-zoo', 'silver & gold');
        
        $this->assertEquals('red & blue, silver & gold', $header->getHeaderLine('FoO-zoo'));
    }

    public function testGetHeaderLineNotExists()
    {
        $this->assertEquals('', $this->headers->getHeaderLine('NotExists'));
    }

    public function testStaleHasHeader()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        $header->withHeader('Qux', 'white');
        $this->assertTrue($header->hasHeader('Foo-Zoo'));
    }

    public function testStaleGetHeaderLine()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue')->withAddedHeader('Foo-Zoo', 'silver & gold');
        $header->withHeader('Qux', 'white');
        $this->assertEquals('red & blue, silver & gold', $header->getHeaderLine('Foo-Zoo'));
    }

    public function testStaleGetHeader()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        $header->withHeader('Qux', 'white');
        $this->assertEquals(['red & blue'], $header->getHeader('Foo-Zoo'));
    }
    
    public function testStaleGetHeaderEmpty()
    {
        $header = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        $header->withHeader('Qux', 'white');
        $this->assertEquals([], $header->getHeader('Foo'));
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
        $header = $this->headers->withoutHeader(['foo', 'bar']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can not change stale object
     */
    public function testErrorOnSetHeaderInStaleObject()
    {
        $this->headers->withHeader('foo', 'bar');
        $this->expectException($this->headers->withHeader('baz', 'raz'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can not change stale object
     */
    public function testErrorOnAppendHeaderInStaleObject()
    {
        $this->headers->withHeader('foo', 'bar');
        $this->expectException($this->headers->withAddedHeader('baz', 'raz'));
    }
}
