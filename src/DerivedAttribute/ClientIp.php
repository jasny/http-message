<?php

namespace Jasny\HttpMessage\DerivedAttribute;

use Jasny\HttpMessage\DerivedAttributeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Determine the client ip, taking proxy settings into consideration
 */
class ClientIp implements DerivedAttributeInterface
{
    /**
     * CIDR address of trusted proxy
     * @var string|boolean
     */
    protected $trustedProxy;
    
    /**
     * Class constructor.
     * 
     * @param array $options  ['trusted_proxy' => string|boolean]
     *    trusted_proxy is a CIDR address of trusted proxy, true to trust all proxies
     * @return self
     */
    public function __construct(array $options = [])
    {
        if (isset($options['trusted_proxy'])) {
            $trustedProxy = $options['trusted_proxy'];
            
            if (is_string($trustedProxy) && !\Jasny\str_contains($trustedProxy, '/')) {
                $trustedProxy .= '/32';
            }

            $this->trustedProxy = $trustedProxy;
        }
    }
    
    
    /**
     * Split a string of IPs
     * 
     * @param string $header
     * @return array
     */
    protected function splitIps($header)
    {
        return $header ? array_map('trim', explode(',', $header)) : [];
    }
    
    /**
     * Extract the `for` part from the Forwarded header
     * 
     * @param string $forwarded
     * @return string
     */
    protected function extractForFromForwardedHeader($forwarded)
    {
        $ips = [];
        $parts = array_map('trim', explode(',', $forwarded));
        
        foreach ($parts as $part) {
            list($key, $value) = explode('=', $part, 2) + [1 => null];
            
            if ($key === 'for') {
                $ips[] = trim($value, '[]');
            }
        }
        
        return $ips;
    }
    
    /**
     * Check if all the items are the same, filtering out empty ones
     * 
     * @param string[] $items
     * @return boolean
     */
    protected function areAllTheSame(...$items)
    {
        $unique = array_unique(array_filter($items), SORT_REGULAR);
        return count($unique) <= 1;
    }
    
    /**
     * Get the forwarded ip
     * 
     * @param ServerRequestInterface $request
     * @param string                 $ip       Connected IP
     * @return string|null
     */
    protected function getForwardedIp(ServerRequestInterface $request, $ip)
    {
        $ips = [$ip];
        
        $forwardedFor = $this->splitIps($request->getHeaderLine('X-Forwarded-For'));
        $forwarded = $this->extractForFromForwardedHeader($request->getHeaderLine('Forwarded'));
        $clientIp = $this->splitIps($request->getHeaderLine('Client-Ip'));
        
        if (!$this->areAllTheSame($forwardedFor, $forwarded, $clientIp)) {
            $msg = 'Only one of `X-Forwarded-For`, `Forwarded` or `Client-Ip` headers should be set';
            throw new \RuntimeException($msg);
        }
        
        $fwd = $forwardedFor ?: $forwarded ?: $clientIp;
        
        if ($fwd) {
            $ips = array_merge($ips, $fwd);
        }
        
        return $this->getTrustedForwardedIp($ips);
    }
    
    /**
     * Select an IP which is within the list of trusted ips
     * 
     * @param array $ips
     * @return string
     */
    protected function getTrustedForwardedIp(array $ips)
    {
        if (is_string($this->trustedProxy)) {
            foreach ($ips as $ip) {
                if (\Jasny\ip_in_cidr($ip, $this->trustedProxy)) {
                    continue;
                }
                return $ip;
            }
        }
        
        return end($ips);
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
        
        if (!isset($params['REMOTE_ADDR'])) {
            return null;
        }
        
        return $this->trustedProxy
            ? $this->getForwardedIp($request, $params['REMOTE_ADDR'])
            : $params['REMOTE_ADDR'];
    }
}
