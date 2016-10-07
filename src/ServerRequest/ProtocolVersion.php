<?php

namespace Jasny\HttpMessage\ServerRequest;

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
        
        return isset($protocol) && $protocol === 'HTTP' ? $version : "1.0";
    }
    
    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        if (!isset($this->protocolVersion)) {
            $this->protocolVersion = $this->determineProtocolVersion();
        }

        return $this->protocolVersion;
    }
}
