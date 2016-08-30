<?php

namespace Jasny\HttpMessage\ServerRequest;

use Psr\Http\Message\StreamInterface;


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
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        if (!isset($this->body)) {
            $this->body = new InputStream();
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
        
        return $this;
    }
}
