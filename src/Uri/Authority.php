<?php

namespace Jasny\HttpMessage\Uri;

/**
 * Uri authority method.
 */
trait Authority
{
    /**
     * Retrieve the user information component of the URI.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    abstract public function getUserInfo();
    
    /**
     * Retrieve the host component of the URI.
     *
     * @return string The URI host.
     */
    abstract public function getHost();
    
    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method will return it as an integer.
     *
     * @return null|int The URI port.
     */
    abstract public function getPort();
    
    
    /**
     * Retrieve the authority component of the URI.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it will not be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        $userInfo = $this->getUserInfo();
        $host = $this->getHost();
        $port = $this->getPort();
        
        if (!$host) {
            return '';
        }
        
        return ($userInfo ? $userInfo . '@' : '') . $host . ($port ? ':' . $port : '');
    }
}
