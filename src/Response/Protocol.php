<?php
namespace Jasny\HttpMessage\Response;

/**
 * ServerRequest header methods
 */
trait Protocol
{

    /**
     * HTTP Response version
     *
     * @var integer 3-digits from 100 to 599
     */
    protected $version = '1.1';
    
    /**
     * Default aviable http version
     * @see https://tools.ietf.org/html/rfc1945 HTTP 1.0
     * @see https://tools.ietf.org/html/rfc2068 HTTP 1.1
     * @see https://tools.ietf.org/html/rfc7540 HTTP 2.0
     * @var string
     */
    private $defVersions = array('1.0', '1.1', '2');
    
    /**
     * Set 
     * @param string $version
     * @throws \InvalidArgumentException
     */
    public function setVersion($version)
    {
        if (! is_string($version)) {
            throw new \InvalidArgumentException("HTTP version must be a string");
        }
        
        if ( ! in_array($this->defVersions, $version)) {
            throw new \InvalidArgumentException("HTTP versions are unknown");
        }
        $this->version = $version;
    }
    
    /**
     * Get response HTTP version
     * 
     * @return string
     */
    public function getVersion(){
        return $this->version;
    }
    
    /**
     * Get protocol string for response
     * @return string
     */
    
    public function getProtocolString(){
        return 'HTTP/' + $this->version;
    }
}
