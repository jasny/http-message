<?php

namespace Jasny\HttpMessage\Response;

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
        return Stream::open('php://temp', 'w+');
    }

    /**
     * Append to body
     *
     * @param string $string
     * @return StreamInterface Returns the body as a stream.
     */
    protected function appendToBody($string)
    {
        $this->getBody()->write($string);
        
        return $this->body;
    }
}
