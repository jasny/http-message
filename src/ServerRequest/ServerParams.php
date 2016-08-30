<?php

namespace Jasny\HttpMessage\ServerRequest;

/**
 * ServerRequest server params methods
 */
trait ServerParams
{
    /**
     * Server parameters, typically $_SERVER
     * @var array
     */
    protected $serverParams;
    
    /**
     * Retrieve server parameters.
     * Typically the $_SERVER superglobal.
     *
     * @return array
     */
    public function getServerParams()
    {
        
    }

    /**
     * Return an instance with the specified server params.
     * 
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @param array $params Array of key/value pairs server parameters.
     * @return static
     */
    public function withServerParams(array $params)
    {
        
    }
}
