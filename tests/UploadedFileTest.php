<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStream;

use Jasny\HttpMessage\UploadedFile;
use Jasny\HttpMessage\Stream;

/**
 * @covers \Jasny\HttpMessage\UploadedFile
 */
class UploadedFileTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStreamDirectory 
     */
    protected $vfs;
    
    /**
     * Info of a successfully uploaded file
     * @var array
     */
    protected $info;
    
    /**
     * @var UploadedFile
     */
    protected $uploadedFile;
    
    /**
     * set up test environmemt
     */
    public function setUp()
    {
        $this->vfs = vfsStream::setup('root', null, array_fill_keys(['tmp', 'assets', 'etc', 'bin'], []));
        $this->vfs->getChild('bin')->chmod(0555); // Not writable
        vfsStream::newFile('php1234.tmp')->at($this->vfs->getChild('tmp'))->setContent("hello world");
        vfsStream::newFile('bar.txt')->at($this->vfs->getChild('assets'))->setContent("the stars");
        vfsStream::newFile('passwd')->at($this->vfs->getChild('etc'))->setContent("root:x:0:0:root:/root:/bin/bash");
        
        $this->info = [
            'name' => 'foo.txt',
            'type' => 'text/plain',
            'size' => 11,
            'tmp_name' => 'vfs://root/tmp/php1234.tmp',
            'error' => UPLOAD_ERR_OK
        ];
        
        $this->uploadedFile = new UploadedFile($this->info, 'qux');
    }

    
    public function testGetClientFilename()
    {
        $this->assertEquals('foo.txt', $this->uploadedFile->getClientFilename());
    }
    
    public function testGetClientMediaType()
    {
        $this->assertEquals('text/plain', $this->uploadedFile->getClientMediaType());
    }
    
    public function testGetSize()
    {
        $this->assertEquals(11, $this->uploadedFile->getSize());
    }
    
    public function testGetError()
    {
        $ok = new UploadedFile(['error' => UPLOAD_ERR_OK]);
        $this->assertEquals(UPLOAD_ERR_OK, $ok->getError());
        
        $noFile = new UploadedFile(['error' => UPLOAD_ERR_NO_FILE]);
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $noFile->getError());
    }
    
    public function testGetErrorDescription()
    {
        $ok = new UploadedFile(['error' => UPLOAD_ERR_OK]);
        $this->assertNull($ok->getErrorDescription());
        
        $noFile = new UploadedFile(['error' => UPLOAD_ERR_NO_FILE]);
        $this->assertEquals("No file was uploaded", $noFile->getErrorDescription());
        
        $unknown = new UploadedFile(['error' => PHP_INT_MAX]);
        $this->assertEquals("An unknown error occured", $unknown->getErrorDescription());
    }
    
        
    public function testGetKey()
    {
        $this->assertEquals('qux', $this->uploadedFile->getKey()); // Non PSR-7
    }
    
    
    public function testMoveTo()
    {
        $this->uploadedFile->moveTo('vfs://root/assets/foo.txt');
        
        $this->assertTrue($this->vfs->getChild('assets')->hasChild('foo.txt'));
        $this->assertEquals('hello world', $this->vfs->getChild('assets')->getChild('foo.txt')->getContent());
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMoveToInvalidPath()
    {
        $this->uploadedFile->moveTo('/*/xyz.txt');
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failed to move uploaded file 'qux' to 'vfs://root/x/foo.txt': No such file or directory
     */
    public function testMoveToNoDir()
    {
        $this->uploadedFile->moveTo('vfs://root/x/foo.txt');
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failed to move uploaded file 'qux' to 'vfs://root/bin/foo': Permission denied
     */
    public function testMoveToFailed()
    {
        $this->uploadedFile->moveTo('vfs://root/bin/foo');
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The uploaded file 'qux' no longer exists or is already moved
     */
    public function testMoveToTwice()
    {
        $this->uploadedFile->moveTo('vfs://root/assets/foo.txt');
        $this->uploadedFile->moveTo('vfs://root/assets/foo.txt');
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage There is no tmp_file for uploaded file: No file was uploaded
     */
    public function testMoveToError()
    {
        $this->uploadedFile = new UploadedFile(['error' => UPLOAD_ERR_NO_FILE]);
        $this->uploadedFile->moveTo('vfs://root/assets/foo.txt');
    }
    
    
    public function testGetStream()
    {
        $stream = $this->uploadedFile->getStream();
        $this->assertInstanceOf(Stream::class, $stream);
        
        $this->assertEquals('hello world', $stream->getContents());
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The uploaded file 'qux' no longer exists or is already moved
     */
    public function testGetStreamAfterMove()
    {
        $this->uploadedFile->moveTo('vfs://root/assets/foo.txt');
        $this->uploadedFile->getStream();
    }
    
    
    public function testAssertIsUploadedFileSucceed()
    {
        $this->uploadedFile = new UploadedFile($this->info, null, true);
        $this->uploadedFile->moveTo('vfs://root/assets/foo.txt');
        
        $this->assertTrue($this->vfs->getChild('assets')->hasChild('foo.txt'));
        $this->assertEquals('hello world', $this->vfs->getChild('assets')->getChild('foo.txt')->getContent());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The specified tmp_name for uploaded file doesn't appear to be uploaded via HTTP POST
     */
    public function testAssertIsUploadedFileFailed()
    {
        $uploadedFile = new UploadedFile(['tmp_name' => 'vfs://root/etc/passwd'] + $this->info, null, true);
        $uploadedFile->moveTo('vfs://root/etc/passwd');
    }
}
