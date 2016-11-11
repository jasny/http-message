<?php

namespace Jasny\HttpMessage;

/**
 * Headers that are linked to the global environment
 */
class ResponseHeaders extends Headers
{
    /**
     * Flag to indicate a stale object
     * @var boolean
     */
    protected $isStale = false;

    /**
     * Create header array from resived array in the Header
     * 
     * @param array $incomingArray 
     */
    public function __construct($incomingArray = [])
    {
        foreach ($incomingArray as $name => $values) {
            foreach ((array)$values as $value) {
                $this->header("$name: $value", false);
            }
        }
    }
    
    
    /**
     * Split a header string in name and value
     * 
     * @param string $header
     * @return array [name, value, key]
     */
    protected function splitHeader($header)
    {
        list($name, $value) = explode(':', $header, 2);
        
        return [trim($name), trim($value), strtolower(trim($name))];
    }
    
    
    /**
     * Check that headers are not already sent
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
     * Check that this instance is in sync with the global environment
     *
     * @throws \RuntimeException
     */
    protected function assertNotStale()
    {
        if ($this->isStale === true) {
            throw new \RuntimeException("Can not change stale object");
        }
    }

    /**
     * Mark that this instance is no longer in sync with the global environment
     */
    protected function turnStale()
    {
        $this->isStale = true;
        
        $list = $this->headersList();
        
        foreach ($list as $header) {
            list($name, $value, $key) = $this->splitHeader($header);
            
            if (!isset($this->headers[$key])) {
                $this->headers[$key] = ['name' => $name, 'values' => []];
            }
            
            $this->headers[$key]['values'][] = $value;
        }
    }

    /**
     * Public function to get object state 
     */
    public function isStale()
    {
        return $this->isStale;
    }
    

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * // Represent the headers as a string
     * foreach ($message->getHeaders() as $name => $values) {
     * echo $name.': '.implode(', ', $values);
     * }
     *
     * // Emit headers iteratively:
     * foreach ($message->getHeaders() as $name => $values) {
     * foreach ($values as $value) {
     * header(sprintf('%s: %s', $name, $value), false);
     * }
     * }
     *
     * @return string[][] Returns an associative array of the message's headers.
     *         Each key is a header name, and each value is an array of strings for
     *         that header.
     */
    public function getHeaders()
    {
        if ($this->isStale()) {
            return parent::getHeaders();
        }
        
        $names = [];
        $values = [];
        $list = $this->headersList();
        
        foreach ($list as $header) {
            list($name, $value, $key) = $this->splitHeader($header);
            
            if (!isset($names[$key])) {
                $names[$key] = $name;
                $values[$key] = [];
            }
            
            $values[$key][] = $value; 
        }
        
        $headers = [];
        
        foreach ($names as $key => $name) {
            $headers[$name] = $values[$key];
        }
        
        return $headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name
     *            Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *         name using a case-insensitive string comparison. Returns false if
     *         no matching header name is found in the message.
     */
    public function hasHeader($name)
    {
        if ($this->isStale()) {
            return parent::hasHeader($name);
        }
        
        $this->assertHeaderName($name);
        
        $find = strtolower($name);
        $found = false;
        
        foreach ($this->headersList() as $header) {
            list(, , $key) = $this->splitHeader($header);
            
            if ($key === $find) {
                $found = true;
                break;
            }
        }
        
        return $found;
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * @param string $name
     *            Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *         header. If the header does not appear in the message, this method MUST
     *         return an empty array.
     */
    public function getHeader($name)
    {
        if ($this->isStale()) {
            return parent::getHeader($name);
        }
        
        $this->assertHeaderName($name);
        
        $find = strtolower($name);
        $values = [];
        
        foreach ($this->headersList() as $header) {
            list(, $value, $key) = $this->splitHeader($header);
            
            if ($key === $find) {
                $values[] = $value;
            }
        }
        
        return $values;
    }
    
    
    /**
     * Abstraction for `withHeader` and `withAddedHeader`
     * 
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @param boolean $add
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     * @throws \RuntimeException if headers are already sent
     */
    protected function withHeaderLogic($name, $value, $add)
    {
        $this->assertHeaderName($name);
        $this->assertHeaderValue($value);
        $this->assertNotStale();
        $this->assertHeadersNotSent();
        
        $request = clone $this;
        $this->turnStale();
        
        foreach ((array)$value as $val) {
            $this->header("{$name}: {$val}", !$add);
        }
        
        return $request;
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        return $this->withHeaderLogic($name, $value, false);
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names.
     * @throws \InvalidArgumentException for invalid header values.
     */
    public function withAddedHeader($name, $value)
    {
        return $this->withHeaderLogic($name, $value, true);
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name)
    {
        $this->assertHeaderName($name);
        
        if (!$this->hasHeader($name)) {
            return $this;
        }
        
        $this->assertNotStale();
        $this->assertHeadersNotSent();
        
        $request = clone $this;
        $this->turnStale();
        $this->headerRemove($name);
        
        return $request;
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
                throw new \RuntimeException("Getting the HTTP headers on PHP CLI requires XDebug");
            }
            
            return xdebug_get_headers();
        }
        
        return headers_list();
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
        header_remove($name);
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
}
