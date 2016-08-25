<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use Psr\Http\Message\ServerRequestInterface;
use Jasny\HttpMessage\Uri;

/**
 * Return the path of the `Referer` header, but only if the referer's host part matches the `Host` header.
 */
class LocalReferer
{
    /**
     * @var boolean
     */
    protected $checkScheme = true;
    
    /**
     * @var boolean
     */
    protected $checkPort = true;
    
    
    /**
     * Class constructor
     * 
     * @param array $options  ['checkScheme' => boolean, 'checkPort' => boolean]
     */
    public function __construct(array $options = [])
    {
        if (isset($options['checkScheme'])) {
            $this->checkScheme = $options['checkScheme'];
        }
        
        if (isset($options['checkPort'])) {
            $this->checkPort = $options['checkPort'];
        }
    }
    
    /**
     * Calculate the derived attribute.
     * 
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        $referer = new Uri($request->getHeaderLine('Referer'));
        
        $refererMatch =
            (!$this->checkScheme || $referer->getScheme() === $uri->getScheme()) &&
            $referer->getHost() === $uri->getHost() &&
            (!$this->checkPort || $referer->getPort() === $uri->getPort());
        
        return $refererMatch ? $referer->getPath() : null;
    }
}
