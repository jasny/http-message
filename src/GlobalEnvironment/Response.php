<?php

namespace Jasny\HttpMessage\GlobalEnvironment;

use Jasny\HttpMessage\Response as Base;
use Jasny\HttpMessage\GlobalEnvironmentInterface;
use Jasny\HttpMessage\GlobalEnvironment\ResponseStatus;
use Jasny\HttpMessage\GlobalEnvironment\Headers;
use Jasny\HttpMessage\OutputBufferStream;
use BadMethodCallException;

/**
 * Http response
 */
class Response extends Base implements GlobalEnvironmentInterface
{
    /**
     * The object is stale if it no longer reflects the global enviroment
     * @var boolean|null
     */
    protected $isStale;
    
    /**
     * Use php://output stream and default php functions work with headers.
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @param boolean $bind   Bind to global environment
     * @return static
     * @throws BadMethodCallException if the response is stale
     * @throws RuntimeException if isn't not possible to open the 'php://output' stream
     */
    public function withGlobalEnvironment($bind = false)
    {
        if ($this->isStale) {
            throw new BadMethodCallException("Unable to use a stale response. Did you mean to rivive it?");
        }
        
        if ($this->isStale === false) {
            return $this->copy();
        }
        
        $response = clone $this;
        $response->isStale = false;
        
        $response->status = $this->createGlobalResponseStatus();
        $response->headers = $this->createGlobalResponseHeaders();
        $response->setBody($this->createOutputBufferStream());
        
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
     * @return static
     */
    public function withoutGlobalEnvironment()
    {
        if ($this->isStale === null) {
            return $this;
        }
        
        $response = clone $this;
        
        $response->copy(); // explicitly make stale
        
        if ($response->body instanceof OutputBufferStream) {
            $response->body = $response->body->withLocalScope();
        }
        
        $response->isStale = null;
        
        return $response;
    }
    
    
    /**
     * The object is stale if it no longer reflects the global environment.
     * Returns null if the object isn't using the globla state.
     * 
     * @return boolean|null
     */
    public function isStale()
    {
        return $this->isStale;
    }
    
    /**
     * Clone the response.
     * Turn stale if the response is bound to the global environment.
     * 
     * @return static  A non-stale response
     * @throws BadMethodCallException when the response is stale
     */
    protected function copy()
    {
        if ($this->isStale) {
            throw new BadMethodCallException("Unable to modify a stale response object");
        }
        
        $response = clone $this;
        
        if ($this->isStale === false) {
            $this->status = new ResponseStatus($this->status);
            $this->headers = new Headers($this->getHeaders());

            $this->isStale = true;
        }
        
        return $response;
    }
    
    /**
     * Revive a stale response
     * 
     * @return $this
     */
    public function revive()
    {
        if ($this->isStale !== true) {
            return $this;
        }
        
        $response = clone $this;
        
        $response->status = $this->createGlobalResponseStatus($this->status)
            ->withProtocolVersion($this->getProtocolVersion());
        $response->headers = $this->createGlobalResponseHeaders($this->getHeaders());
        
        if ($response->body instanceof OutputBufferStream) {
            $response->body->useGlobally();
        }
        
        $response->isStale = false;
        
        return $response;
    }

    
    /**
     * Create a new global response status.
     * @codeCoverageIgnore
     * 
     * @param ResponseStatus|null $status
     * @return ResponseStatus
     */
    protected function createGlobalResponseStatus($status = null)
    {
        return new ResponseStatus($status);
    }
    
    /**
     * Create a new global response status.
     * @codeCoverageIgnore
     * 
     * @param array|null $headers
     * @return Headers
     */
    protected function createGlobalResponseHeaders($headers = null)
    {
        return isset($headers) ? new ResponseHeaders($headers) : new ResponseHeaders();
    }
    
    /**
     * Create a new output buffer stream.
     * @codeCoverageIgnore
     * 
     * @return OutputBufferStream
     */
    protected function createOutputBufferStream()
    {
        return new OutputBufferStream();
    }
}
