<?php

namespace Jasny\HttpMessage\ServerRequest;

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
     * Reset the parsed body, excepted if it was explicitly set
     */
    abstract protected function resetParsedBody();
    
    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        if (!isset($this->body)) {
            $this->body = Stream::open('data://text/plain,', 'r');
        }
        
        return $this->body;
    }
    
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
    
    /**
     * Return an instance with the specified message body.
     *
     * @param StreamInterface $body
     * @return static
     */
    public function withBody(StreamInterface $body)
    {
        $request = clone $this;
        $request->setBody($body);
        
        return $request;
    }
}
