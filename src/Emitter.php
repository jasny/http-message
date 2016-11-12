<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Jasny\HttpMessage\EmitterInterface;
use Jasny\HttpMessage\Wrap;

/**
 * Emit the HTTP response
 */
class Emitter implements EmitterInterface
{
    use Wrap\Headers;
    
    /**
     * Emit the HTTP status (and protocol version)
     * 
     * @param ResponseInterface $response
     */
    public function emitStatus(ResponseInterface $response)
    {
        $this->assertHeadersNotSent();
        
        $protocolVersion = $response->getProtocolVersion() ?: '1.1';
        
        $statusCode = $response->getStatusCode() ?: 200;
        $reasonPhrase = $response->getReasonPhrase();
        
        $this->header("HTTP/$protocolVersion $statusCode $reasonPhrase");
    }
    
    /**
     * Emit the HTTP headers
     * 
     * @param ResponseInterface $response
     */
    public function emitHeaders(ResponseInterface $response)
    {
        foreach ($response->getHeaders() as $name => $values) {
            foreach (array_values($values) as $value) {
                $this->header("$name: $value", $i === 0);
            }
        }
    }
    
    /**
     * Emit the HTTP body
     * 
     * @param ResponseInterface $response
     * @throws \RuntimeException
     */
    public function emitBody(ResponseInterface $response)
    {
        $output = $this->createOutputStream();
        
        if (!$output) {
            throw new \RuntimeException("Failed to open output stream");
        }
        
        $handle = $response->getBody()->detach();
        
        stream_copy_to_stream($handle, $output);
    }
    
    
    /**
     * Emit the full HTTP response
     * 
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response)
    {
        $this->emitStatus($response);
        $this->emitHeaders($response);
        $this->emitBody($response);
    }
    
    /**
     * Invoke the emitter as PSR-7 middleware
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next = null)
    {
        if (isset($next) && !is_callable($next)) {
            throw new \InvalidArgumentException("Expected next to be callable");
        }
        
        $this->emit($response);
        
        if (isset($next)) {
            $next($request, $response);
        }
    }
    
    
    /**
     * Create `php://output` stream
     * @codeCoverageIgnore
     * 
     * @return resource
     */
    protected function createOutputStream()
    {
        return fopen('php://output', 'w');
    }
}
