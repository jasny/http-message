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
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function copy();

    /**
     * Get the server parameters
     * 
     * @return array
     */
    abstract public function getServerParams();
    
    
    /**
     * Determine the request target based on the server params
     * 
     * @return string
     */
    protected function determineMethod()
    {
        $params = $this->getServerParams();
        return isset($params['REQUEST_METHOD']) ? strtoupper($params['REQUEST_METHOD']) : '';
    }
    
    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        if (!isset($this->method)) {
            $this->method = $this->determineMethod();
        }
        
        return $this->method;
    }


    /**
     * Assert method is valid
     * 
     * @param string $method
     * @throws \InvalidArgumentException
     */
    protected function assertMethod($method)
    {
        if (!is_string($method)) {
            $type = (is_object($method) ? get_class($method) . ' ' : '') . gettype($method);
            throw new \InvalidArgumentException("Method should be a string, not a $type");
        }
        
        if (preg_match('/[^a-z\-]/i', $method)) {
            $type = (is_object($method) ? get_class($method) . ' ' : '') . gettype($method);
            throw new \InvalidArgumentException("Invalid method '$method': "
                . "Method may only contain letters and dashes");
        }
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
        $this->assertMethod($method);
        
        $request = $this->copy();
        $request->method = $method;
        
        return $request;
    }
    
}
