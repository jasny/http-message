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
     * Retrieves the HTTP protocol version as a string.
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
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
        
        $this->version = (string)$version;
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
