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
        if (! isset($this->body)) {
            $this->body = Stream::open('php://memory', 'w+');
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

    /**
     * Return an instance with the specified message body.
     *
     * @param StreamInterface $body            
     * @return static
     */
    public function withBody(StreamInterface $body)
    {
        $response = clone $this;
        $response->setBody($body);
        
        return $response;
    }
}
