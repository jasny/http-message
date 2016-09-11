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
    protected $queryParams = [];

    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function turnStale();
    
    
    /**
     * Retrieves the deserialized query string arguments, if any.
     * Typically the $_GET superglobal.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * @param array $query Array of query string arguments
     * @return static
     */
    public function withQueryParams(array $query)
    {
        $request = $this->turnStale();
        
        $request->queryParams = $query;
        return $request;
    }
}
