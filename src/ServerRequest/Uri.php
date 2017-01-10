<?php

namespace Jasny\HttpMessage\ServerRequest;

use Psr\Http\Message\UriInterface;
use Jasny\HttpMessage\Uri as UriObject;

/**
 * ServerRequest URI methods
 */
trait Uri
{
    /**
     * @var UriInterface
     */
    protected $uri;
    
    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function copy();

    /**
     * Get the server parameters
     * 
     * @return array
     */
    abstract public function getServerParams();
    
    /**
     * Create a new instance with this header
     * 
     * @param string          $name
     * @param string|string[] $value
     * @return self
     */
    abstract public function withHeader($name, $value);
    
    
    /**
     * Map server params for URI
     * 
     * @param array $params
     * @return array
     */
    protected function mapUriPartsFromServerParams(array $params)
    {
        $parts = [];
        
        $map = [
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PWD' => 'password',
            'HTTP_HOST' => 'host',
            'SERVER_PORT' => 'port',
            'REQUEST_URI' => 'path',
            'QUERY_STRING' => 'query'
        ];
        
        foreach ($map as $param => $key) {
            if (isset($params[$param])) {
                $parts[$key] = $params[$param];
            }
        }
        
        return $parts;
    }
    
    /**
     * Determine the URI base on the server parameters
     * 
     * @return string
     */
    protected function determineUri()
    {
        $params = $this->getServerParams();
        
        $parts = $this->mapUriPartsFromServerParams($params);
        
        if (
            isset($params['SERVER_PROTOCOL']) &&
            \Jasny\str_starts_with(strtoupper($params['SERVER_PROTOCOL']), 'HTTP/')
        ) {
            $parts['scheme'] = !empty($params['HTTPS']) && $params['HTTPS'] !== 'off' ? 'https' : 'http';
        }
        
        if (isset($parts['host'])) {
            list($parts['host']) = explode(':', $parts['host'], 2); // May include the port
        }
        
        if (isset($parts['path'])) {
            $parts['path'] = parse_url($parts['path'], PHP_URL_PATH); // Includes the query string
        }

        return new UriObject($parts);
    }
    
    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance representing the URI
     *     of the request.
     */
    public function getUri()
    {
        if (!isset($this->uri)) {
            $this->uri = $this->determineUri();
        }
        
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri          New request URI to use.
     * @param boolean      $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $request = $this->copy();
        $request->uri = $uri;
        
        if (!$preserveHost) {
            $request = $request->withHeader('Host', $request->uri->getHost());
        }
        
        return $request;
    }
}
