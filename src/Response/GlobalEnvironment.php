<?php

namespace Jasny\HttpMessage\Response;

use Jasny\HttpMessage\Headers;
use Jasny\HttpMessage\ResponseHeaders;
use Jasny\HttpMessage\ResponseStatus;
use Psr\Http\Message\StreamInterface;

/**
 * ServerRequest methods for using the global enviroment
 */
trait GlobalEnvironment
{
    /**
     * The object is stale if it no longer reflects the global enviroment
     * @var boolean|null
     */
    protected $isStale;
    
    /**
     * HTTP headers
     * @var HeadersInterface
     */
    protected $headers;
    
    /**
     * HTTP Response status
     * @var ResponseStatus
     */
    protected $status;
    
    
    /**
     * Get the body
     * @return StreamInterface
     */
    abstract public function getBody();
    
    /**
     * Get the headers
     * @return HeadersInterface
     */
    abstract public function getHeaders();
    
    /**
     * Function for the protocol version
     * @return string
     */
    abstract public function getProtocolVersion();
    
    
    /**
     * Use php://output stream and default php functions work with headers.
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @return self
     * @throws RuntimeException if isn't not possible to open the 'php://output' stream
     */
    public function withGlobalEnvironment()
    {
        $response = $this->turnStale();
        
        $response->getBody()->useGlobally();
        $response->headers = new ResponseHeaders();
        $response->status = (new ResponseStatus($this->getProtocolVersion()));
        $response->status->useGlobally();
        
        return $response;
    }
    
    /**
     * Return object that is disconnected from superglobals
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @return self
     */
    public function withoutGlobalEnvironment()
    {
        $response = $this->turnStale();
        
        $response->getBody()->useLocally();
        $response->headers = new Headers($this->headers);
        $response->status->useLocally();
        
        return $response;
    }
    
    
    /**
     * Disconnect the global enviroment, turning stale
     * Headers object should be replaced by a normal array.
     * 
     * @return self  Clone of non-stale request
     */
    protected function turnStale()
    {
        $response = clone $this;
        $this->isStale = true;
        $this->headers = $this->getHeaders();
        
        return $response;
    }
    
    /**
     * The object is stale if it no longer reflects the global enviroment.
     * Returns null if the object isn't using the globla state.
     * 
     * @return boolean If current object are stale 
     */
    public function isStale()
    {
        return $this->isStale;
    }
}
