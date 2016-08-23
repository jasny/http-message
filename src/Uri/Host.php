<?php

namespace Jasny\HttpMessage\Uri;

/**
 * Uri host methods.
 */
trait Host
{
    /**
     * @var string
     */
    protected $host = '';
    
    /**
     * Check if the hostname is valid a valid domain name according to RFC 3986 and RFC 1123
     * 
     * @param string $hostname
     * @return boolean
     */
    protected function isValidDomain($hostname)
    {
        return preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $hostname)
            && preg_match("/^.{1,253}$/", $hostname)
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $hostname)
            && !preg_match("/^\d{0,3}\.\d{0,3}\.\d{0,3}\.\d{0,3}$/", $hostname);
    }
    
    /**
     * Check if the hostname is a valid IPv4 address
     * 
     * @param string $hostname
     * @return boolean
     */
    protected function isValidIpv4($hostname)
    {
        return preg_match('/^([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.'
            . '([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.'
            . '([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.'
            . '([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])$/', $hostname);
    }
    
    /**
     * Check if the hostname is a valid IPv6 address, including square brackets
     * 
     * @param string $hostname
     * @return boolean
     */
    protected function isValidIpv6($hostname)
    {
        return preg_match('/^\[((?:[0-9a-f]{1,4}))((?::[0-9a-f]{1,4}))*::((?:[0-9a-f]{1,4}))'
            . '((?::[0-9a-f]{1,4}))*|((?:[0-9a-f]{1,4}))((?::[0-9a-f]{1,4})){7}\]$/', $hostname);
    }
    
    
    /**
     * Retrieve the host component of the URI.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the host
     * 
     * @param string $host
     */
    protected function setHost($host)
    {
        $host = strtolower($host);
        
        if ($host !== '' && !$this->isValidDomain($host) && !$this->isValidIpv4($host) && !$this->isValidIpv6($host)) {
            throw new \InvalidArgumentException("Invalid hostname '$host'");
        }
        
        $this->host = $host;
    }

    /**
     * Return an instance with the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return static A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        $uri = clone $this;
        $uri->setHost($host);
        
        return $uri;
    }
}
