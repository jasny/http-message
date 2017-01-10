<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ResponseInterface;
use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\EmitterInterface;
use Jasny\HttpMessage\Emitter;

/**
 * Http response
 */
class Response implements ResponseInterface
{
    use Response\ProtocolVersion;
    use Response\Status;
    use Response\Headers;
    use Response\Body;
    
    /**
     * The object is bound to the global enviroment
     * @var boolean
     */
    protected $isGlobal = false;
    
    
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

        return !isset($this->status) || $this->status->isStale() ||
            !isset($this->headers) || $this->headers->isStale() ||
            !isset($this->body) || $this->body->getMetadata('uri') !== 'php://output';
    }
    
    /**
     * Revive a stale object
     * 
     * @return static
     */
    public function revive()
    {
        if ($this->isStale() !== true) {
            return $this;
        }
        
        $response = clone $this;
        
        if (isset($this->status) && $this->status->isStale()) {
            $response->status = $this->status->withStatus(200);
        }
        
        if (isset($this->headers) && $this->headers->isStale()) {
            $response->headers = new ResponseHeaders($this->getHeaders());
        }
        
        if (isset($this->body)) {
            $response->body = new OutputBufferStream();
            $response->body->write((string)$this->body);
        }
        
        return $response;
    }
}
