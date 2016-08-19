<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use Jasny\HttpMessage\DerivedAttribute;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Determine the client ip, taking proxy settings into consideration
 */
class ClientIp implements DerivedAttribute
{
    /**
     * Get the value of the trusted_proxy attribute
     * 
     * @param ServerRequestInterface $request
     * @return boolean|string
     */
    protected function getTrustedProxyAttribute(ServerRequestInterface $request)
    {
        $proxy = $request->getAttribute('trusted_proxy', false);
        
        if (is_string($proxy) && !\Jasny\str_contains($proxy, '/')) {
            $proxy .= '/32';
        }
        
        return $proxy;
    }

    /**
     * Use the forwarded IP address, but only if forwarded by a trusted proxy,
     * 
     * @param string $proxy
     * @param array  $params
     * @return string|false
     */
    protected function useForwardedIp($proxy, $params)
    {
        if (isset($params['HTTP_CLIENT_IP'])) {
            $forwarded = $params['HTTP_CLIENT_IP'];
        } elseif (isset($params['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = $params['HTTP_X_FORWARDED_FOR'];
        } else {
            return false;
        }

        if (is_string($proxy) && isset($params['REMOTE_ADDR']) && \Jasny\ip_in_cidr($params['REMOTE_ADDR'], $proxy)) {
            list($ip) = explode(',', $forwarded);
        } elseif ($proxy === true) {
            $ips = explode(',', $forwarded);
            $ip = trim(end($ips));
        }
        
        return isset($ip) ? $ip : false;
    }
    
    /**
     * Calculate the derived attribute.
     * 
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $proxy = $this->getTrustedProxyAttribute($request);
        $params = $request->getServerParams();
        
        return $this->useForwardedIp($proxy, $params) ?:
            (isset($params['REMOTE_ADDR']) ? $params['REMOTE_ADDR'] : null);
    }
}
