<?php

namespace Jasny\HttpMessage\Uri;

/**
 * Uri port methods.
 */
trait Port
{
    /**
     * @var int
     */
    protected $port;
    
    /**
     * Check if the port is in the valid range
     * 
     * @param int $port
     * @return boolean
     */
    protected function isValidPort($port)
    {
        return (int)$port >= 1 && (int)$port <= 65535;
    }
    
    /**
     * Get the default port for the scheme
     * 
     * @return int|null
     */
    abstract protected function getDefaultPort();
    

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method will return it as an integer.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        return $this->getDefaultPort() !== $this->port ? $this->port : null;
    }
    
    /**
     * Set the port
     * 
     * @param int|null $port
     */
    protected function setPort($port)
    {
        if ((string)$port !== '' && !$this->isValidPort($port)) {
            throw new \InvalidArgumentException("Invalid port '$port'");
        }
        
        $this->port = $port ? (int)$port : null;
    }

    /**
     * Return an instance with the specified port.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return static A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        $uri = clone $this;
        $uri->setPort($port);
        
        return $uri;
    }
}
