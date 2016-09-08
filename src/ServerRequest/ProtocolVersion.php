<?php

namespace Jasny\HttpMessage\ServerRequest;

/**
 * ServerRequest protocol version methods
 */
trait ProtocolVersion
{
    /**
     * @var string 
     */
    protected $protocolVersion;
    
    /**
     * @var array 
     */
    protected $params;
    
    
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

    /**
     * Set the HTTP protocol version.
     * 
     * @param string $version HTTP protocol version
     * @throws \InvalidArgumentException for invalid versions
     */
    protected function setProtocolVersion($version)
    {
        if ($version != '' && $version !== "1.0" && $version !== "1.1" && $version !== "2.0") {
            throw new \InvalidArgumentException("Invalid HTTP protocol version '$version'");
        }
        
        $this->protocolVersion = (string)$version;
    }
    
    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * @param string $version HTTP protocol version
     * @return static
     * @throws \InvalidArgumentException for invalid versions
     */
    public function withProtocolVersion($version)
    {
        $request = clone $this;
        $request->setProtocolVersion($version);
        
        return $request;
    }
}
