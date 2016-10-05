<?php
namespace Jasny\HttpMessage\Response;

/**
 * ServerRequest header methods
 */
trait protocolVersion
{

    /**
     * HTTP Response version
     *
     * @var string
     */
    protected $version = '1.1';

    /**
     * Default aviable http version
     * 
     * @see https://tools.ietf.org/html/rfc1945 HTTP/1.0
     * @see https://tools.ietf.org/html/rfc2068 HTTP/1.1
     * @see https://tools.ietf.org/html/rfc7540 HTTP/2
     * @var string
     */
    private $defVersions = array(
        '1.0',
        '1.1',
        '2'
    );

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
        
        if (! is_string($version)) {
            throw new \InvalidArgumentException("HTTP version must be a string or float");
        }
        
        if ($version == '2.0') {
            $version == '2';
        }
        
        if (! in_array($version, $this->defVersions)) {
            throw new \InvalidArgumentException("HTTP versions {$version} are unknown");
        }
        
        $this->version = $version;
    }

    /**
     * Get response HTTP version
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->version;
    }

    /**
     * Get protocol string for response
     * 
     * @return string
     */
    public function getProtocolString()
    {
        return 'HTTP/' + $this->version;
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
}
