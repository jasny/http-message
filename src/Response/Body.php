<?php

namespace Jasny\HttpMessage\Response;

use Psr\Http\Message\StreamInterface;
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
     * The object is stale if it no longer reflects the global environment.
     * Returns null if the object isn't using the globla state.
     * 
     * @var boolean|null
     */
    abstract public function isStale();
    
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
        if ($body instanceof OutputBufferStream && $this->isStale() === false) {
            $body->useGlobally();
        }
        
        $this->body = $body;
    }
}
