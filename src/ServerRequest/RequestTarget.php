<?php

namespace Jasny\HttpMessage\ServerRequest;

/**
 * ServerRequest request target methods
 */
trait RequestTarget
{
    /**
     * @var StreamInterface
     */
    protected $requestTarget;

    
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
    protected function determineRequestTarget()
    {
        $params = $this->getServerParams();
        
        return isset($params['REQUEST_URI'])
            ? $params['REQUEST_URI']
            : (isset($params['REQUEST_METHOD']) && $params['REQUEST_METHOD'] === 'OPTIONS' ? '*' : '/');
    }
    
    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method returns the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if (!isset($this->requestTarget)) {
            $this->requestTarget = $this->determineRequestTarget();
        }
        
        return $this->requestTarget;
    }
    

    /**
     * Assert that the request target is a string
     * 
     * @param string $requestTarget
     * @throws \InvalidArgumentException
     */
    protected function assertRequestTarget($requestTarget)
    {
        if (!is_string($requestTarget)) {
            $type = (is_object($requestTarget) ? get_class($requestTarget) . ' ' : '') . gettype($requestTarget);
            throw new \InvalidArgumentException("Request target should be a string, not a $type");
        }
    }

    /**
     * Return an instance with the specific request-target.
     *
     * @see http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param string $requestTarget
     * @return static
     * @throws \InvalidArgumentException if $requestTarget is not a string
     */
    public function withRequestTarget($requestTarget)
    {
        $this->assertRequestTarget($requestTarget);
        
        $request = $this->copy();
        $request->requestTarget = $requestTarget;
        
        return $request;
    }
}
