<?php

namespace Jasny\HttpMessage\ServerRequest;

/**
 * ServerRequest method methods
 */
trait Method
{
    /**
     * Request method
     * @var string
     */
    protected $method;
    
    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * @param string $method Case-sensitive method.
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        
    }
    
}
