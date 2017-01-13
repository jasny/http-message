<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Jasny\TestHelper;

use Jasny\HttpMessage\OutputBufferStream;

/**
 * @covers Jasny\HttpMessage\OutputBufferStream
 */
class OutputBufferStreamTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
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
     * @param string uri
     * @return OutputBufferStream|MockObject
     */
    protected function getStream($uri)
    {
        $this->handle = $uri ? fopen($uri, 'a+') : null;
        
        $stream = $this->getMockBuilder(OutputBufferStream::class)->disableOriginalConstructor()
            ->setMethods(['obGetStatus', 'obGetContents', 'obClean', 'obFlush', 'obGetLength'])
            ->getMock();

        $stream->expects($this->any())->method('obGetStatus')->willReturn([
            "name" => "default output handler",
            "type" => 0
        ]);
        
        $refl = new \ReflectionProperty(OutputBufferStream::class, 'handle');
        $refl->setAccessible(true);
        $refl->setValue($stream, $this->handle);
        
        return $stream;
    }

    public function testLocalIsSeekable()
    {
        $stream = $this->getStream('php://temp');
        $this->assertTrue($stream->isSeekable());
    }

    public function testLocalIsReadable()
    {
        $stream = $this->getStream('php://temp');
        $this->assertTrue($stream->isReadable());
    }

    public function testLocalIsWritable()
    {
        $stream = $this->getStream('php://temp');
        $this->assertTrue($stream->isWritable());
    }

    public function testLocalWrite()
    {
        $stream = $this->getStream('php://temp');
        
        $count = $stream->write('Foo-Baz');
        
        $this->assertSame(7, $count);
        $this->assertStreamDataEquals('Foo-Baz', $this->handle);
    }

    public function testLocalClose()
    {
        $stream = $this->getStream('php://temp');
        
        $stream->close();
        $this->assertSame('Unknown', get_resource_type($this->handle));
    }

    public function testLocalDetach()
    {
        $stream = $this->getStream('php://temp');
        
        $detached = $stream->detach();
        
        $this->assertInternalType('resource', $detached);
        $this->assertEquals("stream", get_resource_type($detached));
        $this->assertSame($this->handle, $detached);
        
        $this->assertNull($stream->getMetadata());
        
        $this->assertNull($stream->detach());
    }

    public function testLocalGetSize()
    {
        $stream = $this->getStream('php://temp');
        
        $this->assertEquals(0, $stream->getSize());
        
        $stream->write('Foo-Baz');
        $this->assertEquals(7, $stream->getSize());
    }

    public function testLocalTell()
    {
        $stream = $this->getStream('php://temp');
        
        $this->assertEquals(0, $stream->tell());

        $stream->write('Foo-Baz');
        $this->assertEquals(7, $stream->tell());
        
        $stream->seek(2);
        $this->assertEquals(2, $stream->tell());
    }

    public function testLocalEOF()
    {
        $stream = $this->getStream('php://temp');
        
        $this->assertFalse($stream->eof());
        
        $stream->read(1);
        $this->assertTrue($stream->eof());

        $stream->write('Foo-Baz');
        $this->assertTrue($stream->eof());
        
        $stream->seek(2);
        $this->assertFalse($stream->eof());
    }

    public function testLocalRead()
    {
        $stream = $this->getStream('php://temp');
        
        $stream->write('Foo-Baz');
        $this->assertEquals('', $stream->read(100));
        
        $stream->seek(3);
        $this->assertEquals('-', $stream->read(1));
        $this->assertEquals('Baz', $stream->read(100));
    }

    public function testLocalGetContents()
    {
        $stream = $this->getStream('php://temp');
        
        $stream->write('Foo-Baz');
        
        $stream->seek(4);
        $this->assertEquals('Baz', $stream->getContents());
    }

    public function testLocalToString()
    {
        $stream = $this->getStream('php://temp');
        
        $stream->write('Foo-Baz');
        $this->assertEquals('Foo-Baz', (string)$stream);
    }

    public function testLocalRewind()
    {
        $stream = $this->getStream('php://temp');
        
        $stream->write('Foo-Baz');
        $stream->rewind();
        
        $this->assertEquals(0, $stream->tell());
        $this->assertEquals('Foo-Baz', $stream->read(100));
    }
    
    public function testLocalClone()
    {
        $stream = $this->getStream('php://temp');
        $stream->write('Hello');

        $clone = clone $stream;
        
        $this->assertEquals('php://temp', $clone->getMetadata('uri'));
        $this->assertEmpty((string)$clone);
    }
    
    
    public function testGlobalIsSeekable()
    {
        $stream = $this->getStream('php://output');
        $this->assertFalse($stream->isSeekable());
    }

    public function testGlobalIsReadable()
    {
        $stream = $this->getStream('php://output');
        $this->assertFalse($stream->isReadable());
    }

    public function testGlobalIsWritable()
    {
        $stream = $this->getStream('php://output');
        $this->assertTrue($stream->isWritable());
    }

    public function testGlobalWrite()
    {
        $stream = $this->getStream('php://output');
        
        $this->expectOutputString('Foo-Zoo');
        
        $stream->write('Foo-Zoo');
    }

    public function testGlobalClose()
    {
        $stream = $this->getStream('php://output');
        $stream->expects($this->once())->method('obFlush');
        
        $stream->close();
        $this->assertSame('Unknown', get_resource_type($this->handle));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't writable
     */
    public function testGlobalWriteClosed()
    {
        $stream = $this->getStream('php://output');
        
        $stream->close();
        
        $stream->write("Foo bar");
    }

    public function testGlobalDetach()
    {
        $stream = $this->getStream('php://output');
        $stream->expects($this->never())->method('obFlush');
        
        $detached = $stream->detach();
        
        $this->assertInternalType('resource', $detached);
        $this->assertEquals("stream", get_resource_type($detached));
        $this->assertSame($this->handle, $detached);
        
        $this->assertNull($stream->getMetadata());
        
        $this->assertNull($stream->detach());
    }

    public function testGlobalGetSize()
    {
        $stream = $this->getStream('php://output');
        $stream->expects($this->exactly(2))->method('obGetLength')->willReturnOnConsecutiveCalls(3, 9);
        
        $this->assertEquals(3, $stream->getSize());
        $this->assertEquals(9, $stream->getSize());
    }

    public function testGlobalTell()
    {
        $stream = $this->getStream('php://output');
        $stream->expects($this->exactly(2))->method('obGetLength')->willReturnOnConsecutiveCalls(3, 9);
        
        $this->assertEquals(3, $stream->tell());
        $this->assertEquals(9, $stream->tell());
    }

    public function testGlobalEof()
    {
        $stream = $this->getStream('php://output');
        $this->assertTrue($stream->eof());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't seekable
     */
    public function testGlobalSeek()
    {
        $stream = $this->getStream('php://output');
        $stream->seek(5);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream isn't seekable
     */
    public function testGlobalRewind()
    {
        $stream = $this->getStream('php://output');
        $stream->rewind();
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to partially read the output buffer, instead cast the stream to a string
     */
    public function testGlobalRead()
    {
        $stream = $this->getStream('php://output');
        $stream->read(1000);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to partially read the output buffer, instead cast the stream to a string
     */
    public function testGlobalGetContents()
    {
        $stream = $this->getStream('php://output');
        $stream->getContents();
    }

    public function testGlobalToString()
    {
        $stream = $this->getStream('php://output');
        $stream->expects($this->once())->method('obGetContents')->willReturn('Red Green Blue');
        
        $this->assertEquals("Red Green Blue", (string)$stream);
    }
    
    public function testGlobalClone()
    {
        $stream = $this->getStream('php://output');
        $stream->expects($this->never())->method('obGetContents');

        $clone = clone $stream;
        
        $this->assertEquals('php://temp', $clone->getMetadata('uri'));
        $this->assertEmpty((string)$clone);
    }
    
    
    public function testUseGlobally()
    {
        $stream = $this->getStream('php://temp');
        $stream->expects($this->once())->method('obClean');

        fwrite($this->handle, "Foo Bar Zoo");
        
        $ret = $stream->useGlobally();
        $this->assertSame($stream, $ret);
        
        $this->assertSame('Unknown', get_resource_type($this->handle), "Temp stream should be closed");
        
        $refl = new \ReflectionProperty(OutputBufferStream::class, 'handle');
        $refl->setAccessible(true);
        $handle = $refl->getValue($stream);
        
        $this->assertInternalType('resource', $handle);
        $this->assertEquals("stream", get_resource_type($handle));
        $this->assertEquals('php://output', stream_get_meta_data($handle)['uri']);
        
        $this->expectOutputString('Foo Bar Zoo');
    }
    
    public function testUseGloballyWhenGlobal()
    {
        $stream = $this->getStream('php://output');
        $ret = $stream->useGlobally();
        
        $this->assertSame($stream, $ret);
        $this->assertAttributeSame($this->handle, 'handle', $stream);
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to create temp stream
     */
    public function testUseGloballyFailure()
    {
        $stream = $this->getMockBuilder(OutputBufferStream::class)
            ->setMethods(['createOutputStream', 'obGetStatus', 'obGetContents', 'obClean', 'obFlush', 'obGetLength'])
            ->getMock();
        
        $stream->expects($this->any())->method('obGetStatus')->willReturn([
            "name" => "default output handler",
            "type" => 0
        ]);
        
        $stream->expects($this->once())->method('createOutputStream')->willReturn(false);
        
        $stream->useGlobally();
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The stream is closed
     */
    public function testUseGloballyWhenClosed()
    {
        $stream = $this->getStream(null);
        $stream->useGlobally();
    }

    public function testWithLocalScope()
    {
        $globalStream = $this->getStream('php://output');
        
        $globalStream->expects($this->once())->method('obGetContents')->willReturn('Foo Bar Zoo');
        
        $stream = $globalStream->withLocalScope();
        $this->assertNotSame($globalStream, $stream);
        
        $this->assertSame('stream', get_resource_type($this->handle));
        
        $refl = new \ReflectionProperty(OutputBufferStream::class, 'handle');
        $refl->setAccessible(true);
        $handle = $refl->getValue($stream);
        
        $this->assertInternalType('resource', $handle);
        $this->assertEquals("stream", get_resource_type($handle));
        $this->assertEquals('php://temp', stream_get_meta_data($handle)['uri']);
        
        fseek($handle, 0);
        $this->assertEquals('Foo Bar Zoo', fread($handle, 1000));
    }
    
    public function testWithLocalScopeWhenLocal()
    {
        $stream = $this->getStream('php://temp');
        $ret = $stream->withLocalScope();
        
        $this->assertSame($stream, $ret);
        $this->assertAttributeSame($this->handle, 'handle', $stream);
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to create temp stream
     */
    public function testWithLocalScopeFailure()
    {
        $stream = $this->getMockBuilder(OutputBufferStream::class)->disableOriginalConstructor()
            ->setMethods(['createTempStream', 'obGetStatus', 'obGetContents', 'obClean', 'obFlush', 'obGetLength'])
            ->getMock();
        
        $stream->expects($this->once())->method('createTempStream')->willReturn(false);

        $this->setPrivateProperty($stream, 'handle', fopen('php://output', 'a+'));
        
        $stream->withLocalScope();
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The stream is closed
     */
    public function testWithLocalScopeWhenClosed()
    {
        $stream = $this->getStream(null);
        $stream->withLocalScope();
    }
    

    public function assertOutputBufferingProvider()
    {
        return [
            ['close'],
            ['getSize'],
            ['tell'],
            ['useGlobally', false]
        ];
    }
    
    /**
     * @dataProvider assertOutputBufferingProvider
     * 
     * @expectedException RuntimeException
     * @expectedExceptionMessage Output buffering is not enabled
     * 
     * @param string $method
     * @param boolean $isGlobal
     */
    public function testAssertOutputBuffering($method, $isGlobal = true)
    {
        $stream = $this->getMockBuilder(OutputBufferStream::class)
            ->setMethods(['obGetLevel', 'obGetContents', 'obClean', 'obFlush', 'obGetLength', 'isGlobal'])
            ->getMock();

        $stream->expects($this->any())->method('isGlobal')->willReturn($isGlobal);
        $stream->expects($this->once())->method('obGetLevel')->willReturn(0);
        
        $stream->$method();
    }
    
    public function testToStringAndAssertOutputBuffering()
    {
        $stream = $this->getMockBuilder(OutputBufferStream::class)
            ->setMethods(['obGetLevel', 'obGetContents', 'obClean', 'obFlush', 'obGetLength', 'isGlobal'])
            ->getMock();

        $stream->expects($this->any())->method('isGlobal')->willReturn(true);
        $stream->expects($this->once())->method('obGetLevel')->willReturn(0);
        
        $contents = (string)$stream;
        
        $this->assertEquals("Output buffering is not enabled", $contents);
    }
}
