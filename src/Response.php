<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ResponseInterface;
use Jasny\HttpMessage\Headers;
use Jasny\HttpMessage\ResponseStatus;
use Jasny\HttpMessage\ResponseHeaders;
use Jasny\HttpMessage\EmitterInterface;
use Jasny\HttpMessage\Emitter;
use Jasny\HttpMessage\OutputBufferStream;
use BadMethodCallException;

/**
 * Http response
 */
class Response implements ResponseInterface
{
    use Response\ProtocolVersionTrait {
        withProtocolVersion as _withProtocolVersion;
    }
    use Response\StatusTrait;
    use Response\HeadersTrait;
    use Response\BodyTrait;

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

        if (isset($this->status) && !$this->status instanceof ResponseStatus) {
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

        if ($response->status instanceof ResponseStatus) {
            $response->status = $response->status->withProtocolVersion($response->getProtocolVersion());
        }

        return $response;
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
