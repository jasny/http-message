<?php

namespace Jasny\HttpMessage\Tests\Integration;

use Http\Psr7Test\UploadedFileIntegrationTest;
use Jasny\HttpMessage\Stream;
use Jasny\HttpMessage\UploadedFile;

class UploadedFileTest extends UploadedFileIntegrationTest
{
    public function createSubject()
    {
        $stream = new Stream('php://memory', 'rw');
        $stream->write('foobar');

        return new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK);
    }
}
