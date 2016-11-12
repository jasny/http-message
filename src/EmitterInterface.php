<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface for an emitter
 */
interface EmitterInterface
{
    /**
     * Emit the HTTP status (and protocol version)
     * 
     * @param ResponseInterface $response
     */
    public function emitStatus(ResponseInterface $response);
    
    /**
     * Emit the HTTP headers
     * 
     * @param ResponseInterface $response
     */
    public function emitHeaders(ResponseInterface $response);
    
    /**
     * Emit the HTTP body
     * 
     * @param ResponseInterface $response
     * @throws \RuntimeException
     */
    public function emitBody(ResponseInterface $response);
}
