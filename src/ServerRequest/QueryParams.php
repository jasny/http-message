<?php

namespace Jasny\HttpMessage\ServerRequest;

/**
 * ServerRequest query params methods
 */
trait QueryParams
{
    /**
     * Query parameters, typically $_GET
     * @var array
     */
    protected $queryParams;
    
    /**
     * Retrieves the deserialized query string arguments, if any.
     * Typically the $_GET superglobal.
     *
     * @return array
     */
    public function getQueryParams()
    {
        
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * @param array $query Array of query string arguments
     * @return static
     */
    public function withQueryParams(array $query)
    {
        
    }
}
