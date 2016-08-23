<?php

namespace Jasny\HttpMessage\Uri;

/**
 * Uri query methods
 */
trait Query
{
    /**
     * @var string
     */
    protected $query = '';
    
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
}
