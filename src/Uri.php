<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\UriInterface;

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
    /**
     * Default ports for supported schemes
     * @var type 
     */
    protected static $defaultPorts = [
        'http' => 80,
        'https' => 443
    ];
    
    
    /**
     * @var string
     */
    protected $scheme = '';
    
    /**
     * @var string
     */
    protected $host = '';
    
    /**
     * @var int
     */
    protected $port;
    
    /**
     * @var string
     */
    protected $user;
    
    /**
     * @var string
     */
    protected $pass;
    
    /**
     * @var string
     */
    protected $path = '';
    
    /**
     * @var string
     */
    protected $query = '';
    
    /**
     * @var string
     */
    protected $fragment = '';
    
    
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
        
        if (isset($parts) && !is_array($parts)) {
            throw new \InvalidArgumentException("Invalid URI");
        }
        
        $this->setUriParts($parts);
    }
    
    /**
     * Set the URI parts
     * 
     * @param array $parts
     */
    protected function setUriParts(array $parts)
    {
        foreach ($parts as $key => $value) {
            if (!method_exists($this, "set$key")) continue;
            $this->{"set$key"}($value);
        }
    }
    
    
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
     * Check if the path is valid according to RFC 3986 section 3.3
     * 
     * @param string $path
     * @return boolean
     */
    protected function isValidPath($path)
    {
        $seg = '(?:[A-Za-z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@]|%[A-Za-z0-9])';
        $segnc = '(?:[A-Za-z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\@]|%[A-Za-z0-9])';
        
        return preg_match('/^(\/|(\/' . $seg . '+|' . $segnc . '+)(\/' . $seg . '*)*)$/', $path);
    }
    
    /**
     * Check if the query string is valid according to RFC 3986 section 3.4
     * 
     * @param string $query
     * @return boolean
     */
    protected function isValidQuery($query)
    {
        return preg_match('/^(?:[A-Za-z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@\/\?]|%[A-Za-z0-9])*$/', $query);
    }
    
    /**
     * Check if the fragment is valid according to RFC 3986 section 3.5
     * 
     * @param string $fragment
     * @return boolean
     */
    protected function isValidFragment($fragment)
    {
        return preg_match('/^(?:[A-Za-z0-9\-\.\_\~\!\$\&\'\(\)\*\+\,\;\=\:\@\/\?]|%[A-Za-z0-9])*$/', $fragment);
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

    /**
     * Retrieve the user information component of the URI.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        if (!isset($this->user)) {
            return '';
        }
        
        return $this->user . ($this->pass ? ':' . $this->pass : '');
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
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method will return it as an integer.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        return $this->scheme && static::$defaultPorts[$this->scheme] !== $this->port ? $this->port : null;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     * The value returned will be percent-encoded.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     * The value returned will be percent-encoded.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->fragment;
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
        
        if ($scheme !== '' && !isset(static::$defaultPorts[$scheme])) {
            throw new \InvalidArgumentException("Invalid or unsupported scheme '$scheme'");
        }
        
        $this->scheme = $scheme;
    }
    
    /**
     * Set the username
     * 
     * @param string $user
     * @throws \InvalidArgumentException for invalid username.
     */
    protected function setUser($user)
    {
        if (\Jasny\str_contains($user, ':')) {
            throw new \InvalidArgumentException("Invalid username '$user': double colon not allowed");
        }
        
        $this->user = (string)$user;
    }
    
    /**
     * Set the password
     * 
     * @param string $password
     */
    protected function setPass($password)
    {
        $this->pass = (string)$password;
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
     * Set the port
     * 
     * @param int|null $port
     */
    protected function setPort($port)
    {
        if ((string)$port !== '' && ((int)$port < 1 || (int)$port > 65535)) {
            throw new \InvalidArgumentException("Invalid port '$port'");
        }
        
        $this->port = $port ? (int)$port : null;
    }
    
    /**
     * Set the path
     * 
     * @param string $path
     */
    protected function setPath($path)
    {
        if ($path !== '' && !$this->isValidPath($path)) {
            throw new \InvalidArgumentException("Invalid path '$path'");
        }
        
        $this->path = (string)$path;
    }
    
    /**
     * Set the query
     * 
     * @param string|array $query
     */
    protected function setQuery($query)
    {
        if (is_array($query)) {
            $query = http_build_query($query);
        }
        
        if (!$this->isValidQuery($query)) {
            throw new \InvalidArgumentException("Invalid query '$query'");
        }
        
        $this->query = (string)$query;
    }
    
    /**
     * Set the fragment
     * 
     * @param string $fragment
     */
    protected function setFragment($fragment)
    {
        if (!$this->isValidFragment($fragment)) {
            throw new \InvalidArgumentException("Invalid fragment '$fragment'");
        }
        
        $this->fragment = (string)$fragment;
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

    /**
     * Return an instance with the specified user information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     * @throws \InvalidArgumentException for invalid username.
     */
    public function withUserInfo($user, $password = null)
    {
        $uri = clone $this;
        $uri->setUser($user);
        $uri->setPass($password);
        
        return $uri;
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

    /**
     * Return an instance with the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash).
     *
     * If an HTTP path is intended to be host-relative rather than path-relative
     * then it must begin with a slash ("/"). HTTP paths not starting with a slash
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * @param string $path The path to use with the new instance.
     * @return static A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        $uri = clone $this;
        $uri->setPath($path);
        
        return $uri;
    }

    /**
     * Return an instance with the specified query string.
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string|array $query The query string to use with the new instance.
     * @return static A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        $uri = clone $this;
        $uri->setQuery($query);
        
        return $uri;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return static A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        $uri = clone $this;
        $uri->setFragment($fragment);
        
        return $uri;
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
