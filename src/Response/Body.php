<?php

namespace Jasny\HttpMessage\Response;

use Jasny\HttpMessage\Stream;
use Jasny\HttpMessage\OutputBufferStream;
use Jasny\HttpMessage\Message;


/**
 * ServerRequest body methods
 */
trait Body
{
    use Message\Body;

    /**
     * Create the default body stream
     * 
     * @return Stream
     */
    protected function createDefaultBody()
    {
        return new OutputBufferStream();
    }
}
