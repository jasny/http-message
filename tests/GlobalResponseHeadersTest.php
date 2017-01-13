<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Jasny\TestHelper;

/**
 * @covers Jasny\HttpMessage\GlobalResponseHeaders
 */
class GlobalResponseHeadersTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    /**
     * @var ResponseHeaders|MockObject
     */
    protected $headers;

    
    public function setUp()
    {
        $this->headers = $this->getMockBuilder(GlobalResponseHeaders::class)
            ->disableOriginalConstructor()
            ->setMethods(['headersList', 'header', 'headerRemove', 'headersSent'])
            ->getMock();
    }

    public function testConstructor()
    {
        $this->headers->expects($this->exactly(4))->method('header')->withConsecutive(
            ["Foo: bar", false],
            ["Color: red", false],
            ["Color: blue", false],
            ["Zoo: monkey", false]
        );
        
        $this->headers->__construct([
            'Foo' => 'bar',
            'Color' => ['red', 'blue'],
            'Zoo' => ['monkey']
        ]);
    }

    protected function expectHeadersList()
    {
        $this->headers->expects($this->once())->method('headersList')
            ->willReturn(['Foo-Zoo: red & blue', 'Bar: xyz', 'foo-zoo : green']);
    }
    
    public function testGetHeaders()
    {
        $this->expectHeadersList();
        
        $this->assertEquals([
            'Foo-Zoo' => ['red & blue', 'green'],
            'Bar' => ['xyz']
        ], $this->headers->getHeaders());
    }

    public function testHasHeader()
    {
        $this->expectHeadersList();
        $this->assertTrue($this->headers->hasHeader('Foo-Zoo'));
    }

    public function testHasHeaderCaseInsensetive()
    {
        $this->expectHeadersList();
        $this->assertTrue($this->headers->hasHeader('FOO-ZOO'));
    }

    public function testHasHeaderNotExists()
    {
        $this->expectHeadersList();
        $this->assertFalse($this->headers->hasHeader('Non-Exists'));
    }

    public function testGetHeader()
    {
        $this->expectHeadersList();
        $this->assertEquals(['red & blue', 'green'], $this->headers->getHeader('Foo-Zoo'));
    }

    public function testGetHeaderCaseInsensetive()
    {
        $this->expectHeadersList();
        $this->assertEquals(['red & blue', 'green'], $this->headers->getHeader('FOO-ZOO'));
    }
    
    public function testGetHeaderNotExists()
    {
        $this->expectHeadersList();
        $this->assertEquals([], $this->headers->getHeader('not-exists'));
    }

    public function testGetHeaderLine()
    {
        $this->expectHeadersList();
        $this->assertEquals('red & blue, green', $this->headers->getHeaderLine('Foo-Zoo'));
    }

    public function testGetHeaderLineNotExists()
    {
        $this->expectHeadersList();
        $this->assertEquals('', $this->headers->getHeaderLine('not-exists'));
    }

    
    public function testWithHeader()
    {
        $this->headers->expects($this->any())->method('headersList')->willReturn([]);
        $this->headers->expects($this->once())->method('header')->with('Foo-Zoo: red & blue', true);
        
        $headers = $this->headers->withHeader('Foo-Zoo', 'red & blue');
        
        $this->assertSame($this->headers, $headers);
    }

    public function testWithHeaderMultiple()
    {
        $this->headers->expects($this->any())->method('headersList')->willReturn([]);
        $this->headers->expects($this->exactly(2))->method('header')
            ->withConsecutive(['Foo-Zoo: red & blue', true], ['Foo-Zoo: green', true]);
        
        $headers = $this->headers->withHeader('Foo-Zoo', ['red & blue', 'green']);
        
        $this->assertSame($this->headers, $headers);
    }

    /**
     * @depends testWithHeader
     */
    public function testWithHeaderOverwrite()
    {
        $this->headers->expects($this->any())->method('headersList')->willReturn([]);
        $this->headers->expects($this->exactly(2))->method('header')
            ->withConsecutive(['Foo-Zoo: red & blue', true], ['foo-zoo: silver & gold', true]);
        
        $this->headers
            ->withHeader('Foo-Zoo', 'red & blue')
            ->withHeader('foo-zoo', 'silver & gold');
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
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Headers already sent in foo.php on line 42
     */
    public function testWithHeaderAlreaySent()
    {
        $this->headers->expects($this->any())->method('headersSent')->willReturn([true, 'foo.php', 42]);
        $this->headers->expects($this->never())->method('header');
        
        $this->headers->withHeader('Foo-Zoo', 'red & blue');
    }

    
    public function testWithAddedHeader()
    {
        $this->headers->expects($this->any())->method('headersList')->willReturn([]);
        $this->headers->expects($this->exactly(2))->method('header')
            ->withConsecutive(['Foo-Zoo: red & blue', false], ['foo-zoo: silver & gold', false]);
        
        $this->headers
            ->withAddedHeader('Foo-Zoo', 'red & blue')
            ->withAddedHeader('foo-zoo', 'silver & gold');
    }

    public function testWithAddedHeaderMultiple()
    {
        $this->headers->expects($this->any())->method('headersList')->willReturn([]);
        $this->headers->expects($this->exactly(3))->method('header')
            ->withConsecutive(
                ['Foo-Zoo: red & blue', false],
                ['foo-zoo: green', false],
                ['foo-zoo: silver & gold', false]
            );
        
        $this->headers
            ->withAddedHeader('Foo-Zoo', 'red & blue')
            ->withAddedHeader('foo-zoo', ['green', 'silver & gold']);
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

    public function testWithoutHeader()
    {
        $this->headers->expects($this->any())->method('headersList')->willReturn(['Foo-Zoo: red & blue']);
        $this->headers->expects($this->once())->method('headerRemove')->with('Foo-Zoo');
        
        $headers = $this->headers->withoutHeader('Foo-Zoo');
        
        $this->assertSame($this->headers, $headers);
    }

    public function testWithoutHeaderNotExists()
    {
        $this->headers->expects($this->once())->method('headersList')->willReturn([]);
        $this->headers->expects($this->never())->method('headerRemove');
        
        $headers = $this->headers->withoutHeader('not-exists');
        
        $this->assertSame($this->headers, $headers);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Header name must be a string
     */
    public function testWithoutHeaderArrayAsName()
    {
        $this->headers->withoutHeader(['foo', 'bar']);
    }
}
