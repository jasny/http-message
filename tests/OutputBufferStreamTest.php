<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Jasny\HttpMessage\OutputBufferStream;

/**
 * @covers Jasny\HttpMessage\OutputBufferStream
 */
class OutputBufferStreamTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var resource
     */
    protected $handle;

    /**
     * Read stream and assert data
     * 
     * @param string  $expect
     * @param resource $resource
     */
    protected function assertStreamDataEquals($expect, $resource)
    {
        fseek($resource, 0);
        $data = fread($resource, 256);
        
        $this->assertEquals($expect, $data);
    }
    
    
    public function testConstruction()
    {
        $stream = new OutputBufferStream();
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to open php://temp stream
     */
    public function testConstructionFailure()
    {
        $stream = $this->getMockBuilder(OutputBufferStream::class)->setMethods(['createTempStream'])->getMock();
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
    }

    
    public function testIsGlobalTrue()
    {
        $stream = $this->getMockBuilder(OutputBufferStream::class)->setMethods(['getMetadata'])->getMock();
        $stream->expects($this->once())->method('getMetadata')->with('uri')->willReturn('php://output');
        
        $this->assertTrue($stream->isGlobal());
    }

    public function testIsGlobalFalse()
    {
        $stream = $this->getMockBuilder(OutputBufferStream::class)->setMethods(['getMetadata'])->getMock();
        $stream->expects($this->once())->method('getMetadata')->with('uri')->willReturn('php://temp');
        
        $this->assertFalse($stream->isGlobal());
    }

    public function testIsGlobalDetached()
    {
        $stream = $this->getMockBuilder(OutputBufferStream::class)->setMethods(['getMetadata'])->getMock();
        $stream->expects($this->once())->method('getMetadata')->with('uri')->willReturn(null);
        
        $this->assertFalse($stream->isGlobal());
    }
    
    
    /**
     * @return OutputBufferStream|MockObject
     */
    protected function getLocalStream()
    {
        $this->handle = fopen('php://temp', 'a+');
        
        $stream = $this->getMockBuilder(OutputBufferStream::class)->disableOriginalConstructor()
            ->setMethods(['createTempStream', 'createOutputStream', 'isGlobal'])->getMock();
        
        $stream->expects($this->never())->method('createTempStream');
        $stream->expects($this->never())->method('createOutputStream');
        $stream->expects($this->any())->method('isGlobal')->willReturn(false);
        
        $refl = new \ReflectionProperty(OutputBufferStream::class, 'handle');
        $refl->setAccessible(true);
        $refl->setValue($stream, $this->handle);
        
        return $stream;
    }

    public function testLocalIsSeekable()
    {
        $stream = $this->getLocalStream();
        $this->assertTrue($stream->isSeekable());
    }

    public function testLocalIsReadable()
    {
        $stream = $this->getLocalStream();
        $this->assertTrue($stream->isReadable());
    }

    public function testLocalIsWritable()
    {
        $stream = $this->getLocalStream();
        $this->assertTrue($stream->isWritable());
    }

    public function testLocalWrite()
    {
        $stream = $this->getLocalStream();
        
        $count = $stream->write('Foo-Baz');
        
        $this->assertSame(7, $count);
        $this->assertStreamDataEquals('Foo-Baz', $this->handle);
    }

    public function testLocalClose()
    {
        $stream = $this->getLocalStream();
        
        $stream->close();
        $this->assertSame('Unknown', get_resource_type($this->handle));
    }

    public function testLocalDetach()
    {
        $stream = $this->getLocalStream();
        
        $detached = $stream->detach();
        
        $this->assertInternalType('resource', $detached);
        $this->assertEquals("stream", get_resource_type($detached));
        $this->assertSame($this->handle, $detached);
        
        $this->assertNull($stream->detach());
    }

    public function testLocalGetSize()
    {
        $stream = $this->getLocalStream();
        
        $this->assertEquals(0, $stream->getSize());
        
        $stream->write('Foo-Baz');
        $this->assertEquals(7, $stream->getSize());
    }

    public function testLocalTell()
    {
        $stream = $this->getLocalStream();
        
        $this->assertEquals(0, $stream->tell());

        $stream->write('Foo-Baz');
        $this->assertEquals(7, $stream->tell());
        
        $stream->seek(2);
        $this->assertEquals(2, $stream->tell());
    }

    public function testLocalEOF()
    {
        $stream = $this->getLocalStream();
        
        $this->assertTrue($stream->eof());
        
        $stream->write('Foo-Baz');
        $this->assertTrue($stream->eof());
    }

    public function testLocalRead()
    {
        $stream = $this->getLocalStream();
        
        $stream->write('Foo-Baz');
        $this->assertEquals('', $stream->read(100));
        
        $stream->seek(3);
        $this->assertEquals('-', $stream->read(1));
        $this->assertEquals('Baz', $stream->read(100));
    }

    public function testLocalGetContents()
    {
        $stream = $this->getLocalStream();
        
        $stream->write('Foo-Baz');
        
        $stream->seek(4);
        $this->assertEquals('Baz', $stream->getContents());
    }

    public function testLocalToString()
    {
        $stream = $this->getLocalStream();
        
        $stream->write('Foo-Baz');
        $this->assertEquals('Foo-Baz', (string)$stream);
    }

    public function testLocalRewind()
    {
        $stream = $this->getLocalStream();
        
        $stream->write('Foo-Baz');
        $stream->rewind();
        
        $this->assertEquals(0, $stream->tell());
        $this->assertEquals('Foo-Baz', $stream->read(100));
    }

    /**
     * Start test ->useGlobally();
     */
    /*public function testGlobalOpen()
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
     * /
    public function testGlobalSeek()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $stream->seek(5);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't seekable
     * /
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
     * /
    public function testGlobalRead()
    {
        $stream = new OutputBufferStream();
        $stream->useGlobally();
        $stream->read(1000);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't writable
     * /
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
     * /
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
    }*/
}
