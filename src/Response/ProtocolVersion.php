<?php

namespace Jasny\HttpMessage\Response;

/**
 * ServerRequest header methods
 */
trait ProtocolVersion
{
    /**
     * HTTP Response version
     *
     * @var string
     */
    protected $protocolVersion;

    /**
     * Get response HTTP version
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        if (!isset($this->protocolVersion)) {
            $this->protocolVersion = $this->setProtocolVersion(reset($this->defVersions));
        }
        
        return $this->protocolVersion;
    }

    /**
     * Get protocol string for response
     * 
     * @return string
     */
    public function getProtocolString()
    {
        return 'HTTP/' + $this->protocolVersionn;
    }

    /**
     *
     * @param string/float $version            
     * @return static
     * @throws \InvalidArgumentException for invalid versions
     */
    public function withProtocolVersion($version)
    {
        $request = clone $this;
        $request->setProtocolVersion($version);
        
        return $request;
    }

    /**
     * Set the HTTP protocol version.
     *
     * @param string $version HTTP protocol version
     * @throws \InvalidArgumentException for invalid versions
     */
    protected function assertProtocolVersion($version)
    {
        if ($version !== "1.0" && $version !== "1.1" && $version !== "2") {
            throw new \InvalidArgumentException("Invalid HTTP protocol version '$version'");
        }
    }

    /**
     * Set HTTP version
     * 
     * @param string/float $version            
     * @throws \InvalidArgumentException
     */
    protected function setProtocolVersion($version)
    {
        if (is_numeric($version)) {
            $version = (string) number_format($version, 1, '.', '');
        }
        
        if ($version == '2.0') {
            $version == '2';
        }
        
        $this->assertProtocolVersion($version);
        $this->protocolVersion = $version;
        
        return $this->protocolVersion;
    }
}
