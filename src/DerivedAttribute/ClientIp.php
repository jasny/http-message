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
     * CIDR address of trusted proxy
     * @var string|boolean
     */
    protected $trustedProxy;
    
    /**
     * Class constructor
     * 
     * @param string|boolean $trustedProxy  CIDR address of trusted proxy, true to trust all proxies
     */
    public function __construct($trustedProxy = false)
    {
        if (is_string($trustedProxy) && !\Jasny\str_contains($trustedProxy, '/')) {
            $trustedProxy .= '/32';
        }
        
        $this->trustedProxy = $trustedProxy;
    }
    
    protected function getForwardedIp(ServerRequestInterface $request)
    {
        if ($request->getHeaderLine('Client-Ip') && $request->getHeaderLine('X-Forwarded-For')) {
            $warning = 'Eiter the `Client-Ip` or `X-Forwarded-For` header should be set, but not both';
            trigger_error($warning, E_USER_WARNING);
            return null;
        }
        
        $forwarded = $request->getHeaderLine('Client-Ip') ?: $request->getHeaderLine('X-Forwarded-For');
        return $forwarded ? array_map('trim', explode(',', $forwarded)) : null;
    }
    
    /**
     * Calculate the derived attribute.
     * 
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $params = $request->getServerParams();
        $ip = isset($params['REMOTE_ADDR']) ? $params['REMOTE_ADDR'] : null;
        
        if (is_string($this->trustedProxy) && $ip && \Jasny\ip_in_cidr($ip, $this->trustedProxy)) {
            $forwarded = $this->getForwardedIp($request);
            if ($forwarded) {
                $ip = reset($forwarded);
            }
        } elseif ($this->trustedProxy === true) {
            $forwarded = $this->getForwardedIp($request);
            if ($forwarded) {
                $ip = end($forwarded);
            }
        }
        
        return $ip;
    }
}
