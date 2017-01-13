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
    protected $serverParams = [];

    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function copy();

    
    /**
     * Retrieve server parameters.
     * Typically the $_SERVER superglobal.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * Return an instance with the specified server params.
     * 
     * Resets all properties that can be derived from the server parameters.
     * 
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @param array $params Array of key/value pairs server parameters.
     * @return static
     */
    public function withServerParams(array $params)
    {
        $request = $this->copy();
        $request->serverParams = $params;
        
        $request->reset();
        
        return $request;
    }
}
