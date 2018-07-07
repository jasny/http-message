<?php

namespace Jasny\HttpMessage\Response;

use Psr\Http\Message\StreamInterface;
use Jasny\HttpMessage\Stream;
use Jasny\HttpMessage\OutputBufferStream;
use Jasny\HttpMessage\Message;


/**
 * ServerRequest body methods
 */
trait BodyTrait
{
    use Message\BodyTrait;

    /**
     * Create the default body stream
     * 
     * @return Stream
     */
    protected function createDefaultBody()
    {
        return new OutputBufferStream();
    }

    /**
     * Set the body
     *
     * @param StreamInterface $body
     */
    protected function setBody(StreamInterface $body)
    {
        $this->body = $body;
    }
}
