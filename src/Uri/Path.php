<?php

namespace Jasny\HttpMessage\Uri;

/**
 * Uri path, query and fragment methods
 */
trait Path
{
    /**
     * @var string
     */
    protected $path = '';
    
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
}
