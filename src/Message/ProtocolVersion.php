<?php

namespace Jasny\HttpMessage\Message;

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
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function copy();

    /**
     * Return default setted protocol for request from 
     * $_SERVER['SERVER_PROTOCOL'] or to response 
     * 
     * @return string
     */
    abstract protected function determineProtocolVersion();

    
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
    protected function assertProtocolVersion($version)
    {
        if (!is_string($version) && !is_numeric($version)) {
            throw new \InvalidArgumentException("HTTP version must be a string or float");
        }
        
        if ($version != '' && $version !== "1.0" && $version !== "1.1" && $version !== "2") {
            throw new \InvalidArgumentException("Invalid HTTP protocol version '$version'");
        }
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * @param string
     * @return static
     * @throws \InvalidArgumentException for invalid versions
     */
    public function withProtocolVersion($version)
    {
        if (is_numeric($version)) {
            $version = number_format((float)$version, $version < 2 ? 1 : 0, '.', '');
        }
        
        $this->assertProtocolVersion($version);
        
        $request = $this->copy();
        $request->protocolVersion = $version;
        
        return $request;
    }
}
