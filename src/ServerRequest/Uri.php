<?php

namespace Jasny\HttpMessage\ServerRequest;

use Psr\Http\Message\UriInterface;

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
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        
    }

    /**
     * Returns an instance with the provided URI.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        
    }
}
