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
        
        if (ob_get_level() === 0)
            ob_start();
    }

    protected function tearDown()
    {
        error_reporting($this->errorReporting);
        \PHPUnit_Framework_Error_Warning::$enabled = $this->errorException;
    }

    public function testDefault()
    {
        $stream = new OutputBufferStream();
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
    }

    public function testIsSeekable()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Baz');
        
        $this->assertTrue($stream->isSeekable());
    }

    public function testIsReadable()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Baz');
        
        $this->assertTrue($stream->isReadable());
    }

    public function testIsWritable()
    {
        $stream = new OutputBufferStream();
        $this->assertTrue($stream->isWritable('Foo-Baz'));
    }

    public function testWrite()
    {
        $stream = new OutputBufferStream();
        $this->assertSame(7, $stream->write('Foo-Baz'));
    }

    public function testClose()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Baz');
        $stream->close();
    }

    public function testDeatach()
    {
        $stream = new OutputBufferStream();
        $deatached = $stream->detach();
        
        $this->assertEquals("", (string)$stream);
        
        $this->assertEquals("stream", get_resource_type($deatached));
        $this->assertEquals("", fread($deatached, 1024));
    }

    public function testGetSize()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Baz');
        
        $this->assertEquals(7, $stream->getSize());
    }

    public function testTell()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Baz');
        
        $this->assertEquals(7, $stream->tell());
    }

    public function testEOF()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Baz');
        
        $this->assertTrue($stream->eof());
    }

    public function testRead()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Baz');
        
        $this->assertEquals('', $stream->read(100));
    }

    public function testReadSeek()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Baz');
        $stream->seek(4);
        
        $this->assertEquals('Baz', $stream->read(100));
    }

    public function testReadRewind()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Baz');
        $stream->rewind();
        
        $this->assertEquals('Foo-Baz', $stream->read(100));
    }

    /**
     * Start test ->useGlobally();
     */
    public function testGlobalOpen()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
    }

    public function testGlobalClose()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $this->assertNull($stream->close());
    }

    public function testGlobalDetach()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        
        $detached = $stream->detach();
        $this->assertEquals("stream", get_resource_type($detached));
        $this->assertNull($stream->getMetadata());
    }

    public function testGlobalCopiedText()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Zoo');
        $stream->rewind();
        $this->assertEquals("Foo-Zoo", $stream->getContents());
        $this->expectOutputString('Foo-Zoo');
        $stream->useGlobally();
        $this->assertEquals("Foo-Zoo", $stream->getContents());
    }

    public function testGlobalToString()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $this->assertEquals("", (string)$stream);
    }

    public function testGlobalGetSize()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        
        $this->expectOutputString('foo');
        $stream->write('foo');
        
        $this->assertEquals(3, $stream->getSize());
    }

    public function testGlobalTell()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        
        $this->expectOutputString('foo');
        $stream->write('foo');
        
        $this->assertEquals(3, $stream->tell());
    }

    public function testGlobalEof()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $this->assertTrue($stream->eof());
    }

    public function testGlobalIsSeekable()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $this->assertFalse($stream->isSeekable());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't seekable
     */
    public function testGlobalSeek()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $stream->seek(5);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't seekable
     */
    public function testGlobalRewind()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $stream->rewind();
    }

    public function testGlobalIsWritable()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $this->assertTrue($stream->isWritable());
    }

    public function testGlobalWrite()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $this->expectOutputString('foo');
        $stream->write('foo');
    }

    public function testGlobalIsReadable()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $this->assertFalse($stream->isReadable());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't readable
     */
    public function testGlobalRead()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $stream->read(1000);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't writable
     */
    public function testGlobalWriteClosed()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        
        $stream->close();
        
        $stream->write("Foo bar");
    }

    public function testGlobalGetContents()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $this->expectOutputString('Foo bar');
        $stream->write("Foo bar");
        $this->assertEquals('Foo bar', $stream->getContents());
    }

    public function testGlobalGetMetadata()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $expect = ["wrapper_type" => "PHP", "stream_type" => "Output", "mode" => "wb", "unread_bytes" => 0, "seekable" => false, "uri" => "php://output", "timed_out" => false, "blocked" => true, "eof" => false];
        
        $this->assertEquals($expect, $stream->getMetadata());
    }

    public function testGlobalGetMetadataByKey()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        
        $this->assertEquals("PHP", $stream->getMetadata("wrapper_type"));
        $this->assertEquals("Output", $stream->getMetadata("stream_type"));
        $this->assertNull($stream->getMetadata("qux"));
    }

    public function testGlobalGetMetadataClosed()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $stream->close();
        
        $this->assertNull($stream->getMetadata("wrapper_type"));
        $this->assertNull($stream->getMetadata("uri"));
    }

    /**
     * Test object after set it in Locally mode after Globally 
     */
    public function testLocallyMetadata()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Zoo');
        $stream->useGlobally();
        $this->assertEquals('Foo-Zoo', $stream->getContents());
        $stream->useLocally();
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
    }

    public function testLocallyCopied()
    {
        $stream = new OutputBufferStream();
        $stream->write('Foo-Zoo');
        $stream->useGlobally();
        $this->assertEquals('Foo-Zoo', $stream->getContents());
        $stream->useLocally();
        $stream->rewind();
        $this->assertEquals('Foo-Zoo', $stream->getContents());
    }

    public function testLocallyCopiedFromGlobal()
    {
        //$this->markTestIncomplete('Look why fseek($handle, 0) do not rewind php://temp, fseek($hendle, 3) works fine!');
        
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $stream->write('Foo-Zoo');
        $this->assertEquals('php://output', $stream->getMetadata('uri'));
        $stream->useLocally();
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
        $stream->rewind();
        $this->assertEquals('Foo-Zoo', $stream->getContents());
    }
}
