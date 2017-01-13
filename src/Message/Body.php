<?php

namespace Jasny\HttpMessage\Message;

use Psr\Http\Message\StreamInterface;
use Jasny\HttpMessage\Stream;

/**
 * ServerRequest body methods
 */
trait Body
{
    /**
     * @var StreamInterface
     */
    protected $body;

    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function copy();

    /**
     * Create the default body stream
     * 
     * @return Stream
     */
    abstract protected function createDefaultBody();
    
    
    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        if (!isset($this->body)) {
            $this->body = $this->createDefaultBody();
        }
        
        return $this->body;
    }

    /**
     * Set the body
     *
     * @param StreamInterface $body
     */
    abstract protected function setBody(StreamInterface $body);
    
    /**
     * Return an instance with the specified message body.
     *
     * @param StreamInterface $body
     * @return static
     */
    public function withBody(StreamInterface $body)
    {
        $request = $this->copy();
        $request->setBody($body);
        
        return $request;
    }
}
