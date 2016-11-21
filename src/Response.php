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
    use Response\GlobalEnvironment;
    use Response\ProtocolVersion;
    use Response\Status;
    use Response\Headers;
    use Response\Body;
    
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
}
