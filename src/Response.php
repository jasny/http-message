<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ResponseInterface;
use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\ResponseStatus;
use Jasny\HttpMessage\GlobalResponseStatus;
use Jasny\HttpMessage\Headers;
use Jasny\HttpMessage\GlobalResponseHeaders;
use Jasny\HttpMessage\EmitterInterface;
use Jasny\HttpMessage\Emitter;
use Jasny\HttpMessage\OutputBufferStream;
use Jasny\HttpMessage\GlobalEnvironmentInterface;

/**
 * Http response
 */
class Response implements ResponseInterface, GlobalEnvironmentInterface
{
    use Response\ProtocolVersion {
        withProtocolVersion as _withProtocolVersion;
    }
    use Response\Status;
    use Response\Headers;
    use Response\Body;
    
    
    /**
     * The object is stale if it no longer reflects the global enviroment
     * @var boolean|null
     */
    protected $isStale;
    
    
    /**
     * Create the default emitter
     * 
     * @return EmitterInterface
     */
    protected function createEmitter()
    {
        return new Emitter();
    }
    
    /**
     * Emit the response
     * 
     * @param EmitterInterface $emitter
     */
    public function emit(EmitterInterface $emitter = null)
    {
        if ($this->isStale) {
            throw new \BadMethodCallException("Unable to emit a stale response object");
        }
        
        if (!isset($emitter)) {
            $emitter = $this->createEmitter();
        }
        
        if (isset($this->status) && !$this->status instanceof GlobalResponseStatus) {
            $emitter->emitStatus($this);
        }
        
        if (isset($this->headers) && !$this->headers instanceof GlobalResponseHeaders) {
            $emitter->emitHeaders($this);
        }
        
        if (isset($this->body) && $this->body->getMetadata('url') !== 'php://output') {
            $emitter->emitBody($this);
        }
    }
    

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @param string
     * @return static
     * @throws \InvalidArgumentException for invalid versions
     */
    public function withProtocolVersion($version)
    {
        $response = $this->_withProtocolVersion($version);
        
        if ($response->status instanceof GlobalResponseStatus) {
            $response->status = $response->status->withProtocolVersion($response->getProtocolVersion());
        }
        
        return $response;
    }

    
    /**
     * Use php://output stream and default php functions work with headers.
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @param boolean $bind   Bind to global environment
     * @return static
     * @throws RuntimeException if isn't not possible to open the 'php://output' stream
     */
    public function withGlobalEnvironment($bind = false)
    {
        if ($this->isStale) {
            throw new \BadMethodCallException("Unable to use a stale response. Did you mean to rivive it?");
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
     * @throws \BadMethodCallException when the response is stale
     */
    protected function copy()
    {
        if ($this->isStale) {
            throw new \BadMethodCallException("Unable to modify a stale response object");
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
     * @return GlobalResponseStatus
     */
    protected function createGlobalResponseStatus($status = null)
    {
        return new GlobalResponseStatus($status);
    }
    
    /**
     * Create a new global response status.
     * @codeCoverageIgnore
     * 
     * @param array|null $headers
     * @return GlobalResponseHeaders
     */
    protected function createGlobalResponseHeaders($headers = null)
    {
        return isset($headers) ? new GlobalResponseHeaders($headers) : new GlobalResponseHeaders();
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
