<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ServerRequestInterface;

/**
 * A derived attribute calculates it's value based on any request parameters.
 * 
 * DirivedAttribute objects are considered immutable; all methods that might change state MUST be implemented such that
 * they retain the internal state of the current message and return an instance that contains the changed state.
 */
interface DerivedAttributeInterface
{
    /**
     * Calculate the derived attribute
     * 
     * @param ServerRequestInterface $request
     * @return mixed
     */
    public function __invoke(ServerRequestInterface $request);
}

