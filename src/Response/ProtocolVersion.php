<?php

namespace Jasny\HttpMessage\Response;

use Jasny\HttpMessage\Message;
use Jasny\HttpMessage\ResponseStatus;

/**
 * ServerRequest protocol version methods
 */
trait ProtocolVersion
{
    use Message\ProtocolVersion {
        Message\ProtocolVersion::withProtocolVersion as _withProtocolVersion;
    }

    /**
     * Get or set HTTP Response status.
     * 
     * @param ResponseStatus $status
     * @return ResponseStatus
     */
    abstract protected function statusObject(ResponseStatus $status = null);
    
    
    /**
     * Determine the protocol versions based on the server params.
     * 
     * @return string
     */
    protected function determineProtocolVersion()
    {
        return "1.1";
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
        
        if ($response->statusObject() !== null) {
            $status = $response->statusObject()->withProtocolVersion($response->protocolVersion);
            $response->statusObject($status);
        }
        
        return $response;
    }
}
