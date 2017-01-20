<?php

namespace Jasny\HttpMessage\ServerRequest;

use PHPUnit_Framework_TestCase;
use Jasny\TestHelper;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\UploadedFile;
use Jasny\HttpMessage\Stream;

/**
 * @covers Jasny\HttpMessage\ServerRequest\UploadedFiles
 */
class UploadedFilesTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    /**
     * @var ServerRequest
     */
    protected $baseRequest;
    
    public function setUp()
    {
        $this->baseRequest = new ServerRequest();
    }
    

    public function testGetUploadedFilesDefault()
    {
        $this->assertSame([], $this->baseRequest->getUploadedFiles());
    }

    /**
     * ServerRequest::setUploadFiles() is protected, because it can only be used for $_FILES
     */
    public function testSetUploadedFiles()
    {
        $refl = new \ReflectionMethod(ServerRequest::class, 'setUploadedFiles');
        $refl->setAccessible(true);
        
        $files = [
            'file' => [
                'name' => 'foo.txt',
                'type' => 'text/plain',
                'size' => 3,
                'tmp_name' => 'data://text/plain,foo',
                'error' => UPLOAD_ERR_OK
            ],
            'failed' => [
                'name' => '',
                'type' => '',
                'size' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE
            ]
        ];
        
        $refl->invokeArgs($this->baseRequest, [&$files]);
        $uploadedFiles = $this->baseRequest->getUploadedFiles();
        
        $this->assertInternalType('array', $uploadedFiles);
        
        $this->assertArrayHasKey('file', $uploadedFiles);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['file']);
        $this->assertEquals(new UploadedFile($files['file'], 'file'), $uploadedFiles['file']);
        
        $this->assertArrayHasKey('failed', $uploadedFiles);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['failed']);
        $this->assertEquals(new UploadedFile($files['failed'], 'failed'), $uploadedFiles['failed']);
    }

    /**
     * ServerRequest::setUploadFiles() is protected, because it can only be used for $_FILES
     */
    public function testGroupUploadedFiles()
    {
        $files = [
            'file' => [
                'name' => 'foo.txt',
                'type' => 'text/plain',
                'size' => 3,
                'tmp_name' => 'data://text/plain,foo',
                'error' => UPLOAD_ERR_OK
            ],
            'colors' => [
                'name' => ['blue' => 'navy.txt', 'red' => 'cherry.html'],
                'type' => ['blue' => 'text/plain', 'red' => 'text/html'],
                'size' => ['blue' => 4, 'red' => 15],
                'tmp_name' => ['blue' => 'data://text/plain,navy', 'red' => 'data://text/html,<h1>cherry</h1>'],
                'error' => ['blue' => UPLOAD_ERR_OK, 'red' => UPLOAD_ERR_OK]
            ]
        ];
        
        $blue = ['name' => 'navy.txt', 'type' => 'text/plain', 'size' => 4, 'tmp_name' => 'data://text/plain,navy',
            'error' => UPLOAD_ERR_OK];
        $red = ['name' => 'cherry.html', 'type' => 'text/html', 'size' => 15,
            'tmp_name' => 'data://text/html,<h1>cherry</h1>', 'error' => UPLOAD_ERR_OK];
        
        $refl = new \ReflectionMethod(ServerRequest::class, 'setUploadedFiles');
        $refl->setAccessible(true);
        $refl->invokeArgs($this->baseRequest, [&$files]);
        
        $uploadedFiles = $this->baseRequest->getUploadedFiles();
        
        $this->assertInternalType('array', $uploadedFiles);
        
        $this->assertArrayHasKey('file', $uploadedFiles);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['file']);
        $this->assertEquals(new UploadedFile($files['file'], 'file'), $uploadedFiles['file']);
        
        $this->assertArrayHasKey('colors', $uploadedFiles);
        $this->assertInternalType('array', $uploadedFiles['colors']);
        
        $this->assertArrayHasKey('blue', $uploadedFiles['colors']);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['colors']['blue']);
        $this->assertEquals(new UploadedFile($blue, 'colors[blue]'), $uploadedFiles['colors']['blue']);
        
        $this->assertArrayHasKey('red', $uploadedFiles['colors']);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['colors']['red']);
        $this->assertEquals(new UploadedFile($red, 'colors[red]'), $uploadedFiles['colors']['red']);
    }

    public function testWithUploadedFiles()
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $request = $this->baseRequest->withUploadedFiles(['file' => $uploadedFile]);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame(['file' => $uploadedFile], $request->getUploadedFiles());
    }

    public function testWithUploadedFilesStructure()
    {
        $file = $this->createMock(UploadedFile::class);
        $blue = clone $file;
        $red = clone $file;
        
        $files = ['file' => $file, 'colors' => compact('blue', 'red')];
        
        $request = $this->baseRequest->withUploadedFiles($files);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame($files, $request->getUploadedFiles());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage 'colors[red]' is not an UploadedFileInterface object, but a string
     */
    public function testWithUploadedFilesInvalidStructure()
    {
        $file = $this->createMock(UploadedFile::class);
        $blue = clone $file;
        $red = 'hello';
        
        $this->baseRequest->withUploadedFiles(['file' => $file, 'colors' => compact('blue', 'red')]);
    }

    public function testWithUploadedFilesTurnStale()
    {
        $refl = new \ReflectionProperty(ServerRequest::class, 'isStale');
        $refl->setAccessible(true);
        $refl->setValue($this->baseRequest, false);
        
        $request = $this->baseRequest->withUploadedFiles([]);
        
        $this->assertTrue($this->baseRequest->isStale());
        $this->assertFalse($request->isStale());
    }
    
    protected function createMockUploadedFile($name, $type, $size, $tmp_name, $error = UPLOAD_ERR_OK)
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->atLeastOnce())->method('getClientFilename')->willReturn($name);
        $file->expects($this->atLeastOnce())->method('getClientMediaType')->willReturn($type);
        $file->expects($this->atLeastOnce())->method('getSize')->willReturn($size);
        $file->expects($this->atLeastOnce())->method('getError')->willReturn($error);

        if ($tmp_name) {
            $fileStream = $this->createMock(Stream::class);
            $fileStream->expects($this->atLeastOnce())->method('getMetadata')->with('uri')->willReturn($tmp_name);
            
            $file->expects($this->atLeastOnce())->method('getStream')->willReturn($fileStream);
        } else {
            $exception = new \RuntimeException();
            $file->expects($this->atLeastOnce())->method('getStream')->willThrowException($exception);
        }
        
        return $file;
    }
    
    public function testUngroupUploadedFiles()
    {
        $keys = ['name', 'type', 'size', 'tmp_name', 'error'];
        
        $files = [
            'qux' => array_combine($keys, ['qux.txt', 'text/plain', 3, '/tmp/xx', UPLOAD_ERR_OK]),
        ];
        
        $refl = new \ReflectionMethod(ServerRequest::class, 'setUploadedFiles');
        $refl->setAccessible(true);
        $refl->invokeArgs($this->baseRequest, [&$files]);
        
        
        $file = $this->createMockUploadedFile('foo.txt', 'text/plain', 3, '/tmp/qq');
        $more = $this->createMockUploadedFile(null, null, null, null, UPLOAD_ERR_NO_FILE);
        
        $colors['red'] = $this->createMockUploadedFile('cherry.html', 'text/html', 15, '/tmp/ab');
        $colors['blue'] = $this->createMockUploadedFile('navy.txt', 'text/plain', 4, '/tmp/cd');
        $colors['green'] = $this->createMockUploadedFile('apple.jpg', 'image/jpeg', null, null, UPLOAD_ERR_INI_SIZE);
        $colors['other'][] = $this->createMockUploadedFile('yellow.txt', 'text/plain', 6, '/tmp/gh');
        $colors['other'][] = $this->createMockUploadedFile('purple.txt', 'text/plain', 36, '/tmp/ij');
        
        $request = $this->baseRequest->withUploadedFiles(compact('file', 'more', 'colors'));
        
        $colorKeys = array_keys($colors);
        
        $this->assertEquals([
            'file' => array_combine($keys, ['foo.txt', 'text/plain', 3, '/tmp/qq', UPLOAD_ERR_OK]),
            'more' => array_combine($keys, [null, null, null, null, UPLOAD_ERR_NO_FILE]),
            'colors' => array_combine($keys, [
                array_combine($colorKeys, ['cherry.html', 'navy.txt', 'apple.jpg', ['yellow.txt', 'purple.txt']]),
                array_combine($colorKeys, ['text/html', 'text/plain', 'image/jpeg', ['text/plain', 'text/plain']]),
                array_combine($colorKeys, [15, 4, null, [6, 36]]),
                array_combine($colorKeys, ['/tmp/ab', '/tmp/cd', null, ['/tmp/gh', '/tmp/ij']]),
                array_combine($colorKeys, [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_INI_SIZE,
                    [UPLOAD_ERR_OK, UPLOAD_ERR_OK]]),
            ])
        ], $files);
    }
}
