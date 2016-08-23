<?php

namespace Jasny\HttpMessage\Uri;

/**
 * Uri scheme methods
 */
trait Scheme
{
    /**
     * Default ports for supported schemes
     * @var array 
     */
    public static $defaultPorts = [
        'http' => 80,
        'https' => 443
    ];
    
    
    /**
     * @var string
     */
    protected $scheme = '';
    
    
    /**
     * Check if scheme is supported
     * 
     * @param string $scheme
     * @return boolean
     */
    protected function isSupportedScheme($scheme)
    {
        return isset(static::$defaultPorts[$scheme]);
    }
    
    /**
     * Get the default port for the schema
     * 
     * @return int|null
     */
    protected function getDefaultPort()
    {
        return $this->scheme !== '' ? static::$defaultPorts[$this->scheme] : null;
    }
    
    
    /**
     * Retrieve the scheme component of the URI.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set the scheme
     * 
     * @param string $scheme
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    protected function setScheme($scheme)
    {
        $scheme = strtolower($scheme);
        
        if ($scheme !== '' && !$this->isSupportedScheme($scheme)) {
            throw new \InvalidArgumentException("Invalid or unsupported scheme '$scheme'");
        }
        
        $this->scheme = $scheme;
    }
    
    /**
     * Return an instance with the specified scheme.
     * 
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return static A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        $uri = clone $this;
        $uri->setScheme($scheme);
        
        return $uri;
    }
}
