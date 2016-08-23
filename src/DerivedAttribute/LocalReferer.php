<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use Psr\Http\Message\ServerRequestInterface;

/**
Return the path of the `Referer` header, but only if the referer's host part matches the `Host` header.
 */
class LocalReferer
{
    /**
     * Calculate the derived attribute.
     * 
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $host = $request->getHeaderLine('Host');
        $referer = $request->getHeaderLine('Referer');
        
        return $host && $referer && parse_url($referer, PHP_URL_HOST) == $host
            ? parse_url($referer, PHP_URL_PATH)
            : null;
    }
}
