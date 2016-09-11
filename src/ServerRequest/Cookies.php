<?php

namespace Jasny\HttpMessage\ServerRequest;

/**
 * ServerRequest cookies methods
 */
trait Cookies
{
    /**
     * Cookies, typically $_COOKIE
     * @var array
     */
    protected $cookies = [];

    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function turnStale();
    
    
    /**
     * Retrieves cookies sent by the client to the server.
     * Typically the $_COOKIE superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies)
    {
        $request = $this->turnStale();
        $request->cookies = $cookies;
        
        return $request;
    }
}
