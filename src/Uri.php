<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\UriInterface;
use Jasny\HttpMessage\Uri;

/**
 * Value object representing a URI.
 *
 * This interface is meant to represent URIs according to RFC 3986 and to
 * provide methods for most common operations. Additional functionality for
 * working with URIs can be provided on top of the interface or externally.
 * Its primary use is for HTTP requests, but may also be used in other
 * contexts.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state are implementeded such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 *
 * Typically the Host header will also be present in the request message.
 *
 * @see http://www.php-fig.org/psr/psr-7/#3-5-psr-http-message-uriinterface
 * @see http://tools.ietf.org/html/rfc3986 (the URI specification)
 */
class Uri implements UriInterface
{
    use Uri\Scheme;
    use Uri\Authority;
    use Uri\UserInfo;
    use Uri\Host;
    use Uri\Port;
    use Uri\Path;
    use Uri\Query;
    use Uri\Fragment;
    
    /**
     * Class constructor.
     * @see http://php.net/parse_url
     * 
     * @param string|array $uri  Full URI string or URI parts
     * @throws \InvalidArgumentException for an invalid uri.
     * @throws \InvalidArgumentException for unsupported or invalid schemes.
     * @throws \InvalidArgumentException for invalid username.
     * @throws \InvalidArgumentException for invalid hostnames.
     * @throws \InvalidArgumentException for invalid ports.
     * @throws \InvalidArgumentException for invalid paths.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function __construct($uri = null)
    {
        $parts = is_string($uri) ? parse_url($uri) : $uri;
        
        if (isset($parts)) {
            if (!is_array($parts)) {
                throw new \InvalidArgumentException("Invalid URI");
            }
        
            $this->setUriParts($parts);
        }
    }
    
    /**
     * Set the URI parts
     * 
     * @param array $parts
     */
    protected function setUriParts(array $parts)
    {
        foreach ($parts as $key => $value) {
            if (!method_exists($this, "set$key")) {
                continue;
            }
            $this->{"set$key"}($value);
        }
    }
    
    /**
     * Get all the parts of the uri
     * 
     * @return array
     */
    protected function getParts()
    {
        return get_object_vars($this);
    }
    
    /**
     * Build a uri from all the parts
     * 
     * @param array $parts
     * @return type
     */
    protected function buildUri(array $parts)
    {
        $uri = 
            ($parts['scheme'] ? "{$parts['scheme']}:" : '') . 
            ($parts['user'] || $parts['host'] ? '//' : '') . 
            ($parts['user'] ? "{$parts['user']}" : '') . 
            ($parts['user'] && $parts['pass'] ? ":{$parts['pass']}" : '') . 
            ($parts['user'] ? '@' : '') . 
            ($parts['host'] ? "{$parts['host']}" : '') . 
            ($parts['port'] ? ":{$parts['port']}" : '');
            
        $uri .=
            ($uri && $parts['path'] && $parts['path'][0] !== '/' ? '/' : '') .
            ($parts['path'] ? "{$parts['path']}" : '') . 
            ($parts['query'] ? "?{$parts['query']}" : '') . 
            ($parts['fragment'] ? "#{$parts['fragment']}" : '');
            
        return $uri;
    }
    
    /**
     * Return the string representation as a URI reference.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
        $parts = $this->getParts();
        return $this->buildUri($parts);
    }
}
