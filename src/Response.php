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
     * Emit the response
     * 
     * @param EmitterInterface $emitter
     */
    public function emit(EmitterInterface $emitter = null)
    {
        if (!isset($emitter)) {
            $emitter = new Emitter();
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
