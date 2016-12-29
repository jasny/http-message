<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;

use Jasny\HttpMessage\Stream;

/**
 * @covers \Jasny\HttpMessage\Stream
 */
class StreamTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $stream = new Stream();
        
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to open 'php://temp' stream
     */
    public function testConstructionFailure()
    {
        $this->getMockBuilder(OutputBufferStream::class)
            ->setMethods(['createTempStream'])
            ->enableOriginalConstructor()
            ->getMock();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructNonResource()
    {
        new Stream('foo');
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructNonStreamResource()
    {
        if (!function_exists('imagecreate')) {
            return $this->markTestSkipped("GD library not loaded, which is used as non-stream resource");
        }
        
        $resource = imagecreate(1, 1);
        new Stream($resource);
    }
    
    
    
    public function testClose()
    {
        $fp = fopen('data://text/plain,foo', 'r');
        $stream = new Stream($fp);
        
        $stream->close();
        
        $this->assertEquals("Unknown", get_resource_type($fp));
    }
    
    public function testDetach()
    {
        $fp = fopen('data://text/plain,foo', 'r');
        $stream = new Stream($fp);
        
        $detached = $stream->detach();
        $this->assertSame($fp, $detached);
        
        $this->assertEquals("", (string)$stream);
        
        $this->assertEquals("stream", get_resource_type($fp));
        $this->assertEquals("foo", fread($fp, 1024));
    }
    
    public function testGetSize()
    {
        $fp = fopen('data://text/plain,foo', 'r');
        $stream = new Stream($fp);
        
        $this->assertEquals(3, $stream->getSize());
    }
    
    
    public function testIsSeekable()
    {
        $this->assertTrue((new Stream(fopen('php://memory', 'w+')))->isSeekable());
        $this->assertTrue((new Stream(fopen('php://input', 'r')))->isSeekable());
        $this->assertTrue((new Stream(fopen('data://text/plain,hello world', 'r')))->isSeekable());
        
        $this->assertFalse((new Stream(fopen('php://output', 'w')))->isSeekable());
    }
    
    public function testIsReadable()
    {
        $this->assertTrue((new Stream(fopen('php://input', 'r')))->isReadable());
        $this->assertTrue((new Stream(fopen('data://text/plain,hello world', 'r')))->isReadable());
        $this->assertTrue((new Stream(fopen('php://memory', 'w+')))->isReadable());
        
        $this->assertFalse((new Stream(fopen('php://output', 'w')))->isReadable());
    }
    
    public function testIsWritable()
    {
        $this->assertTrue((new Stream(fopen('php://memory', 'w+')))->isWritable());
        $this->assertTrue((new Stream(fopen('php://output', 'w')))->isWritable());
        
        $this->assertFalse((new Stream(fopen('php://input', 'r')))->isWritable());
        $this->assertFalse((new Stream(fopen('data://text/plain,hello world', 'r')))->isWritable());
    }
    
    
    public function testSeek()
    {
        $fp = fopen("data://text/plain,blue red yellow", 'r');
        $stream = new Stream($fp);
        
        $stream->seek(5);
        
        $this->assertEquals("red yellow", $stream->read(1024));
    }
    
    public function testSeekCur()
    {
        $fp = fopen("data://text/plain,blue red yellow", 'r');
        $stream = new Stream($fp);
        
        $stream->seek(5, SEEK_CUR);
        $this->assertEquals("red", $stream->read(3));
        
        $stream->seek(1, SEEK_CUR);
        $this->assertEquals("yellow", $stream->read(6));
        
        $stream->seek(-3, SEEK_CUR);
        $this->assertEquals("lo", $stream->read(2));
    }
    
    public function testSeekEnd()
    {
        $fp = fopen("data://text/plain,blue red yellow", 'r');
        $stream = new Stream($fp);
        
        $stream->seek(-6, SEEK_END);
        $this->assertEquals("yellow", $stream->read(6));
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't seekable
     */
    public function testSeekNonSeekable()
    {
        $fp = fopen('php://output', 'w');
        $stream = new Stream($fp);
        
        $stream->seek(5);
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testSeekClosed()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        fclose($fp);
        $stream->seek(5);
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testSeekOutOfBounds()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        $stream->seek(500);
    }

    
    public function testTell()
    {
        $fp = fopen("data://text/plain,blue red yellow", 'r');
        $stream = new Stream($fp);
        
        $this->assertEquals(0, $stream->tell());
        
        $stream->read(4);
        $this->assertEquals(4, $stream->tell());
        
        $stream->read(5);
        $this->assertEquals(9, $stream->tell());
        
        $stream->read(100);
        $this->assertEquals(15, $stream->tell());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testTellClosed()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        fclose($fp);
        $stream->tell();
    }
    
    
    public function testEof()
    {
        $fp = fopen("data://text/plain,blue red yellow", 'r');
        $stream = new Stream($fp);
        
        $this->assertFalse($stream->eof());
        
        $stream->read(4);
        $this->assertFalse($stream->eof());
        
        $stream->read(100);
        $this->assertFalse($stream->eof());
        
        $stream->read(1);
        $this->assertTrue($stream->eof());
    }

    public function testEofClosed()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        fclose($fp);
        $this->assertTrue($stream->eof());
    }

    
    public function testWrite()
    {
        $fp = fopen('php://memory', 'w+');
        $stream = new Stream($fp);
        
        $written = $stream->write("Foo bar");
        $this->assertEquals(7, $written);
        
        fseek($fp, 0);
        $contents = fread($fp, 1024);
        $this->assertEquals("Foo bar", $contents);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't writable
     */
    public function testWriteNonWritable()
    {
        $fp = fopen('data://text/plain,foo', 'r');
        $stream = new Stream($fp);
        
        $stream->write("Foo bar");
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWriteClosed()
    {
        $fp = fopen('php://memory', 'w+');
        $stream = new Stream($fp);
        
        fclose($fp);
        $stream->write("Foo bar");
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testWriteFailed()
    {
        $this->markTestIncomplete("Should use vfsStream or custom stream to simulate a write fail");
    }
    
    
    public function testRead()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        $contents = $stream->read(1024);
        $this->assertEquals("blue red yellow", $contents);
    }
    
    public function testReadInParts()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        $this->assertEquals("blue", $stream->read(4));
        $this->assertEquals(" red", $stream->read(4));
        $this->assertEquals(" yel", $stream->read(4));
        $this->assertEquals("low", $stream->read(4));
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't readable
     */
    public function testReadNonReadable()
    {
        $fp = fopen('php://output', 'w');
        $stream = new Stream($fp);
        
        $stream->read(1024);
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testReadClosed()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        fclose($fp);
        $stream->read(1024);
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testReadFailed()
    {
        $this->markTestIncomplete("Should use vfsStream or custom stream to simulate a read fail");
    }

    
    public function testGetContents()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        $this->assertEquals("blue red yellow", $stream->getContents());
    }
    
    public function testGetContentsRest()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        $stream->read(5);
        $this->assertEquals(5, $stream->tell());
        
        $this->assertEquals("red yellow", $stream->getContents());
    }
    
    
    public function testToString()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        $this->assertEquals("blue red yellow", (string)$stream);
    }
    
    public function testToStringRewind()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        $stream->read(5);
        $this->assertEquals(5, $stream->tell());
        
        $this->assertEquals("blue red yellow", (string)$stream);
    }
    
    public function testToStringClosed()
    {
        $fp = fopen('data://text/plain,blue red yellow', 'r');
        $stream = new Stream($fp);
        
        fclose($fp);
        $this->assertEquals('', (string)$stream);
    }
    
    
    public function testGetMetadata()
    {
        $fp = fopen('data://text/plain,foo', 'r');
        $stream = new Stream($fp);
        
        $expect = [
            "mediatype" => "text/plain",
            "base64" => false,
            "wrapper_type" => "RFC2397",
            "stream_type" => "RFC2397",
            "mode" => "r",
            "unread_bytes" => 0,
            "seekable" => true,
            "uri" => "data://text/plain,foo"
        ];
        
        $this->assertEquals($expect, $stream->getMetadata());
    }
    
    public function testGetMetadataByKey()
    {
        $fp = fopen('data://text/plain,foo', 'r');
        $stream = new Stream($fp);
        
        $this->assertEquals("text/plain", $stream->getMetadata("mediatype"));
        $this->assertEquals("RFC2397", $stream->getMetadata("wrapper_type"));
        $this->assertEquals("data://text/plain,foo", $stream->getMetadata("uri"));
        $this->assertNull($stream->getMetadata("qux"));
    }
    
    public function testGetMetadataClosed()
    {
        $fp = fopen('data://text/plain,foo', 'r');
        $stream = new Stream($fp);
        
        fclose($fp);
        
        $this->assertNull($stream->getMetadata());
        $this->assertNull($stream->getMetadata("uri"));
    }
    
    
    public function testClone()
    {
        $stream = new Stream();
        $stream->write('Hello');

        $clone = clone $stream;
        
        $this->assertEquals('php://temp', $clone->getMetadata('uri'));
        $this->assertEmpty((string)$clone);
    }
    
    
    public function testOpen()
    {
        $stream = Stream::open('data://text/plain,foo', 'r');
        
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals('foo', (string)$stream);
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testOpenFailed()
    {
        @Stream::open('nonexistent://foo', 'r');
    }
}
