<?php

namespace Jasny\HttpMessage\Response;

use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\Headers;
use Jasny\HttpMessage\HeadersInterface;
use Jasny\HttpMessage\ResponseHeaders;
use Jasny\HttpMessage\ResponseStatus;
use Jasny\HttpMessage\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * ServerRequest methods for using the global enviroment
 */
trait GlobalEnvironment
{
    /**
     * The object is bound to the global enviroment
     * @var boolean
     */
    protected $isGlobal = false;
    
    
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
     * @return string[][]
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
     * @return Response
     * @throws RuntimeException if isn't not possible to open the 'php://output' stream
     */
    public function withGlobalEnvironment($bind = false)
    {
        if ($this->isGlobal) {
            return $this;
        }
        
        $response = clone $this;
        
        $response->getBody()->useGlobally();
        $response->headersObject(new ResponseHeaders());
        $response->statusObject((new ResponseStatus($this->getProtocolVersion())))->useGlobally();
        
        $response->isGlobal = true;

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
     * @return Response
     */
    public function withoutGlobalEnvironment()
    {
        if (!$this->isGlobal) {
            return $this;
        }
        
        $response = clone $this;
        
        $response->statusObject()->useLocally();

        $headers = $this->getHeaders();
        $response->headersObject(new Headers($headers));
        
        $body = $this->getBody();
        if ($body instanceof Stream) {
            $body->useLocally();
        }
        
        $response->isGlobal = false;
        
        return $response;
    }

    /**
     * The object is stale if it no longer reflects the global enviroment.
     * Returns null if the object isn't using the global state.
     * 
     * @return boolean If current object are stale 
     */
    public function isStale()
    {
        if (!$this->isGlobal) {
            return null;
        }

        $status = $this->statusObject();
        $headers = $this->headersObject();
        $body = $this->getBody();
        
        return !isset($status) || $status->isStale() || !isset($headers) || $headers->isStale() ||
            !isset($body) || $body->getMetadata('uri') !== 'php://output';
    }
}
