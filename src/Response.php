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

/**
 * Http response
 */
class Response implements ResponseInterface
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
        if (!isset($emitter)) {
            $emitter = $this->createEmitter();
        }
        
        if (isset($this->status) && !$this->status->isGlobal()) {
            $emitter->emitStatus($this);
        }
        
        if (isset($this->headers) && !$this->headers instanceof ResponseHeaders) {
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
     * @return Response
     * @throws RuntimeException if isn't not possible to open the 'php://output' stream
     */
    public function withGlobalEnvironment($bind = false)
    {
        if ($this->isGlobal) {
            return $this;
        }
        
        $response = clone $this;
        
        $response->status = (new GlobalResponseStatus())->withProtocolVersion($this->getProtocolVersion());
        $response->headers = new GlobalHeaders();
        $response->setBody(new OutputBufferStream());
        
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
     * @return Response
     */
    public function withoutGlobalEnvironment()
    {
        if ($this->isStale === null) {
            return $this;
        }
        
        $response = clone $this;
        
        $response->turnStale();
        $response->isStatle = null;
        
        return $response;
    }
    
    
    /**
     * The object is stale if it no longer reflects the global environment.
     * Returns null if the object isn't using the globla state.
     * 
     * @var boolean|null
     */
    public function isStale()
    {
        return $this->isStale;
    }
    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return ServerRequest  A non-stale request
     * @throws \BadMethodCallException when the request is already stale
     */
    protected function turnStale()
    {
        if ($this->isStale) {
            throw new \BadMethodCallException("Unable to modify a stale server request object");
        }
        
        $response = clone $this;
        
        $this->status = new ResponseStatus($this->status);
        $this->headers = new Headers($this->getHeaders());
        
        if ($this->body instanceof OutputBufferStream) {
            $this->body = $this->body->withoutGlobalEnvironment();
        }
        
        $this->isStale = true;
        
        return $response;
    }
    
    /**
     * Revive a stale server request
     * 
     * @return ServerRequest
     */
    public function revive()
    {
        if ($this->isStale !== true) {
            return $this;
        }
        
        $request = new static();
        
        $request->status = (new GlobalResponseStatus($this->status))->withProtocolVersion($this->getProtocolVersion());
        $request->headers = new GlobalResponseHeaders($this->getHeaders());
        $request->body = new OutputBufferStream();
    }
}
