<?php

namespace Jasny\HttpMessage\ServerRequest;

use Psr\Http\Message\StreamInterface;

/**
 * ServerRequest parsed body methods
 */
trait ParsedBody
{
    /**
     * Post data (typically $_POST) or parsed body
     * @var array
     */
    protected $postData;
    
    /**
     * Stats taken when reading the parsed body
     * @var array
     */
    protected $parsedBodyStats;
    
    
    /**
     * Get a header as string
     * 
     * @param string $name
     * @return string
     */
    abstract function getHeaderLine($name);
    
    /**
     * Get the body
     * 
     * @return StreamInterface
     */
    abstract function getBody();
    
    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method will
     * return the contents of $_POST.
     *
     * Otherwise, this method returns the results of deserializing
     * the request body content; as parsing returns structured content.
     *
     * @return null|array|object|mixed The deserialized body parameters, if any.
     */
    public function getParsedBody()
    {
        if ($this->parsedBodyStats !== false) {
            
        }
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * @param null|array|object|mixed $data The deserialized body data.
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is provided.
     */
    public function withParsedBody($data)
    {
        
    }
}
