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
     * @return void
     */
    public function emitStatus(ResponseInterface $response);
    
    /**
     * Emit the HTTP headers
     * 
     * @param ResponseInterface $response
     * @return void
     */
    public function emitHeaders(ResponseInterface $response);
    
    /**
     * Emit the HTTP body
     * 
     * @param ResponseInterface $response
     * @throws \RuntimeException
     * @return void
     */
    public function emitBody(ResponseInterface $response);
}
