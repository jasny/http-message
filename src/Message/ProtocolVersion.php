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
     * Retrieves the HTTP protocol version as a string.
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        if (!isset($this->protocolVersion)) {
            $this->protocolVersion = '1.0';
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
        if (!is_string($version)){
            throw new \InvalidArgumentException("HTTP version must be a string");
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
        $this->assertProtocolVersion($version);
        
        $request = clone $this;
        $request->protocolVersion = (string)$version;
        
        return $request;
    }
}
