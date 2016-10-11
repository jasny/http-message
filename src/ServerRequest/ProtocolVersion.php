<?php

namespace Jasny\HttpMessage\ServerRequest;

use Jasny\HttpMessage\Message;

/**
 * ServerRequest protocol version methods
 */
trait ProtocolVersion
{
    use Message\ProtocolVersion;

    /**
     * Get the server parameters
     * 
     * @return array
     */
    abstract public function getServerParams();

    /**
     * Determine the protocol versions based on the server params
     * 
     * @return string
     */
    protected function determineProtocolVersion()
    {
        $params = $this->getServerParams();
        
        if (isset($params['SERVER_PROTOCOL'])) {
            list($protocol, $version) = explode('/', $params['SERVER_PROTOCOL']) + [1 => null];
        }
        
        return isset($protocol) && $protocol === 'HTTP' ? $version : "1.1";
    }
}
