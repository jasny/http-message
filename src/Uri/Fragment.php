<?php

namespace Jasny\HttpMessage\Uri;

/**
 * Uri fragment methods
 */
trait Fragment
{
    /**
     * @var string
     */
    protected $fragment = '';
    
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
}
