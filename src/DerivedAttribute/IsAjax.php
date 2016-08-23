<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use Jasny\HttpMessage\DerivedAttribute;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Check if request is an AJAX request.
 */
class IsAjax implements DerivedAttribute
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
