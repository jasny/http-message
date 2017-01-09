<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use Jasny\HttpMessage\DerivedAttributeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Check if request is an XHR (AJAX) request.
 */
class IsXhr implements DerivedAttributeInterface
{
    /**
     * Calculate the derived attribute.
     * 
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $requestedWith = $request->getHeaderLine('X-Requested-With');
        return strtolower($requestedWith) === 'xmlhttprequest';
    }
}
