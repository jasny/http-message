<?php

namespace Jasny\HttpMessage\ServerRequest;

use Psr\Http\Message\StreamInterface;
use Jasny\HttpMessage\Stream;
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
        return Stream::open('data://text/plain,', 'r');
    }

    /**
     * Reset the parsed body, excepted if it was explicitly set
     */
    abstract protected function resetParsedBody();

    /**
     * Set the body
     *
     * @param StreamInterface $body
     */
    protected function setBody(StreamInterface $body)
    {
        $this->body = $body;
        $this->resetParsedBody();
    }
}
