<?php

namespace Jasny\HttpMessage\Response;

use Psr\Http\Message\StreamInterface;
use Jasny\HttpMessage\Stream;

/**
 * ServerRequest body methods
 */
trait Body
{
    /**
     * 
     * @var string
     */
    protected $defaultStream = 'php://temp';
    /**
     * 
     * @var string
     */
    protected $defaultMode = 'w+';

    /**
     * Set the body
     *
     * @param StreamInterface $body
     */
    protected function setBody(StreamInterface $body)
    {
        $this->body = $body;
    }

    /**
     * Append to body
     *
     * @param
     *            $string
     * @return StreamInterface Returns the body as a stream.
     */
    protected function appendToBody($string)
    {
        $this->getBody()->write($string);
        
        return $this->body;
    }
}
