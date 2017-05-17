<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Jasny\HttpMessage\EmitterInterface;
use Jasny\HttpMessage\Wrap;
use Jasny\HttpMessage\ResponseStatus;

/**
 * Emit the HTTP response
 */
class Emitter implements EmitterInterface
{
    use Wrap\Headers;

    /**
     * Get the response header for a status code
     * 
     * @param string $protocolVersion
     * @param int    $statusCode
     * @param string $reasonPhrase
     * @return string
     */
    protected function getStatusHeader($protocolVersion, $statusCode, $reasonPhrase)
    {
        if (empty($reasonPhrase)) {
            $reasonPhrase = (new ResponseStatus($statusCode))->getReasonPhrase();
        }
        
        return "HTTP/{$protocolVersion} {$statusCode} {$reasonPhrase}";
    }
    
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

        $header = $this->getStatusHeader($protocolVersion, $statusCode, $reasonPhrase);
        $this->header($header);
    }
    
    /**
     * Emit the HTTP headers
     * 
     * @param ResponseInterface $response
     */
    public function emitHeaders(ResponseInterface $response)
    {
        foreach ($response->getHeaders() as $name => $values) {
            foreach (array_values((array)$values) as $i => $value) {
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
        
        $response->getBody()->rewind();
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
        if ($response instanceof \Jasny\HttpMessage\Response) {
            $response->emit($this);
            return;
        }
        
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
