<?php

namespace Jasny\HttpMessage\Uri;

/**
 * Uri authority method.
 */
trait Authority
{
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
        
        if (!$host) return '';
        
        return ($userInfo ? $userInfo . '@' : '') . $host . ($port ? ':' . $port : '');
    }
}
