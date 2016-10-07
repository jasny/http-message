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
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        if (!isset($this->body)) {
            $this->body = Stream::open($this->defaultStream, $this->defaultMode);
        }
        
        return $this->body;
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
