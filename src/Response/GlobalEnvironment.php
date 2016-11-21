<?php

namespace Jasny\HttpMessage\Response;

use Jasny\HttpMessage\Headers;
use Jasny\HttpMessage\HeadersInterface;
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
     * Get or set HTTP headers object
     * 
     * @param HeadersInterface $headers
     * @return HeadersInterface
     */
    abstract protected function headersObject(HeadersInterface $headers = null);
    
    /**
     * Get or set HTTP Response status
     * 
     * @param ResponseStatus $status
     * @return ResponseStatus
     */
    abstract protected function statusObject(ResponseStatus $status = null);
    
    
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
     * @param boolean $bind   Bind to global environment
     * @return self
     * @throws RuntimeException if isn't not possible to open the 'php://output' stream
     */
    public function withGlobalEnvironment($bind = false)
    {
        if (isset($this->isStale)) {
            return $this;
        }
        
        $response = clone $this;
        
        $response->getBody()->useGlobally();
        $response->headersObject(new ResponseHeaders());
        $response->statusObject((new ResponseStatus($this->getProtocolVersion())))->useGlobally();
        
        $response->isStale = false;

        if (!$bind) {
            // This will copy the headers and body from the global environment
            $response = $response->withoutGlobalEnvironment();
        }
        
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
        if ($this->isStale !== false) {
            return $this;
        }
        
        $response = $this->turnStale();
        
        $response->getBody()->useLocally();
        $response->headersObject(clone $this->headersObject());
        $response->statusObject()->useLocally();
        
        $response->isStale = null;
        
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
        $this->headers = new Headers($this->getHeaders());
        
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
