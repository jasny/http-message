<?php

namespace Jasny\HttpMessage\Wrap;

/**
 * Wrap PHP network extension functions
 * @link http://php.net/manual/en/ref.network.php
 */
trait Headers
{
    /**
     * Assert that headers haven't already been sent
     * 
     * @throws \RuntimeException
     */
    protected function assertHeadersNotSent()
    {
        list($sent, $file, $line) = $this->headersSent();
        
        if ($sent) {
            throw new \RuntimeException("Headers already sent in $file on line $line");
        }
    }
    
    
    /**
     * Wrapper for `header` function
     * @link http://php.net/manual/en/function.header.php
     * @codeCoverageIgnore
     * 
     * @param string  $string
     * @param boolean $replace
     * @param int     $http_response_code
     */
    protected function header($string, $replace = true, $http_response_code = null)
    {
        header($string, $replace, $http_response_code);
    }
    
    /**
     * Wrapper for `header` function
     * @link http://php.net/manual/en/function.header-remove.php
     * @codeCoverageIgnore
     * 
     * @param string $name
     */
    protected function headerRemove($name = null)
    {
        header_remove(...func_get_args());
    }
    
    /**
     * Wrapper for `headers_sent` function
     * @link http://php.net/manual/en/function.headers-sent.php
     * @codeCoverageIgnore
     * 
     * @return array [boolean, string, int]
     */
    protected function headersSent()
    {
        $ret = headers_sent($file, $line);
        
        return [$ret, $file, $line];
    }
    
    /**
     * Wrapper for `headers_list` function.
     * Uses `xdebug_get_headers` on the PHP CLI as `headers_list` will always return an empty array.
     * @link http://php.net/manual/en/function.headers-list.php
     * @codeCoverageIgnore
     * 
     * @return array
     */
    protected function headersList()
    {
        if (php_sapi_name() === 'cli') {
            if (!function_exists('xdebug_get_headers')) {
                throw new \Exception("Getting the HTTP headers on PHP CLI requires XDebug");
            }
            
            return xdebug_get_headers();
        }
        
        return headers_list();
    }
    
    /**
     * Wrapper around `http_response_code` function
     * @link http://php.net/manual/en/function.http-response-code.php
     * @codeCoverageIgnore
     * 
     * @param int|null $code
     * @return int
     */
    protected function httpResponseCode($code = null)
    {
        return http_response_code($code);
    }
}
