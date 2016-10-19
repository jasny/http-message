<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use Jasny\HttpMessage\OutputBufferStream;

/**
 * @covers \Jasny\HttpMessage\OutputBufferStream
 */
class OutputBufferStreamTest extends PHPUnit_Framework_TestCase
{
    protected $errorReporting;
    protected $errorException;

    protected function setUp()
    {
        $this->errorReporting = error_reporting();
        $this->errorException = \PHPUnit_Framework_Error_Warning::$enabled;
    }

    protected function tearDown()
    {
        error_reporting($this->errorReporting);
        \PHPUnit_Framework_Error_Warning::$enabled = $this->errorException;
    }

    public function testClose()
    {
        $stream = new OutputBufferStream();
        $this->assertNull($stream->close());
    }

    public function testDetach()
    {
        $stream = new OutputBufferStream();
        
        $detached = $stream->detach();
        $this->assertEquals("stream", get_resource_type($detached));
        $this->assertNull($stream->getMetadata());
        
    }
    
    public function testToString()
    {
        $stream = new OutputBufferStream();
        $this->assertEquals("", (string)$stream);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failed to get size of the stream
     */
    public function testGetSize()
    {
        $stream = new OutputBufferStream();
        $stream->getSize();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failed to get the position of the stream
     */
    public function testTell()
    {
        $stream = new OutputBufferStream();
        $stream->tell();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failed to move position on the end of the stream
     */
    public function testEof()
    {
        $stream = new OutputBufferStream();
        $stream->eof();
    }

    public function testIsSeekable()
    {
        $stream = new OutputBufferStream();
        $this->assertFalse($stream->isSeekable());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't seekable
     */
    public function testSeek()
    {
        $stream = new OutputBufferStream();
        $stream->seek(5);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't seekable
     */
    public function testRewind()
    {
        $stream = new OutputBufferStream();
        $stream->rewind();
    }

    public function testIsWritable()
    {
        $stream = new OutputBufferStream();
        $this->assertTrue($stream->isWritable());
    }
    /**
     * @runInSeparateProcess
     */
    public function testWrite()
    {
        $stream = new OutputBufferStream();
        $this->expectOutputString('foo');
        $stream->write('foo');
    }

    public function testIsReadable()
    {
        $stream = new OutputBufferStream();
        $this->assertFalse($stream->isReadable());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't readable
     */
    public function testRead()
    {
        $stream = new OutputBufferStream();
        $stream->read(1000);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Current object sourse are closed
     */
    public function testWriteClosed()
    {
        $stream = new OutputBufferStream();
        $stream->close();
        $stream->write("Foo bar");
    }

    public function testGetContents()
    {
        $stream = new OutputBufferStream();
        $this->expectOutputString('Foo bar');
        $stream->write("Foo bar");
        $this->assertEquals('', $stream->getContents());
    }

    public function testGetMetadata()
    {
        $stream = new OutputBufferStream();
        $expect = [ "wrapper_type" => "PHP", "stream_type" => "Output", "mode" => "wb", "unread_bytes" => 0, "seekable" => false, "uri" => "php://output", "timed_out" => false, "blocked" => true, "eof" => false];
        
        $this->assertEquals($expect, $stream->getMetadata());
    }

    public function testGetMetadataByKey()
    {
        $stream = new OutputBufferStream();
        
        $this->assertEquals("PHP", $stream->getMetadata("wrapper_type"));
        $this->assertEquals("Output", $stream->getMetadata("stream_type"));
        $this->assertNull($stream->getMetadata("qux"));
    }

    public function testGetMetadataClosed()
    {
        $stream = new OutputBufferStream();
        $stream->close();
        
        $this->assertNull($stream->getMetadata("wrapper_type"));
        $this->assertNull($stream->getMetadata("uri"));
    }
}
