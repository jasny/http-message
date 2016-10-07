<?php

namespace Jasny\HttpMessage\Message;

/**
 * ServerRequest header methods
 */
trait Headers
{
    
    /**
     * HTTP headers
     *
     * @var array
     */
    protected $headers;

    /**
     * Assert that the header value is a string
     *
     * @param string $name
     * @throws \InvalidArgumentException
     */
    protected function assertHeaderName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException("Header name should be a string");
        }
        
        if (!preg_match('/^[a-zA-Z]\w*(\-\w+)*$/', $name)) {
            throw new \InvalidArgumentException("Invalid header name '$name'");
        }
    }

    /**
     * Assert that the header value is a string
     *
     * @param string|string[] $value
     * @throws \InvalidArgumentException
     */
    protected function assertHeaderValue($value)
    {
        if (!is_string($value) && (!is_array($value) || array_product(array_map('is_string', $value)) === 0)) {
            throw new \InvalidArgumentException("Header value should be a string or an array of strings");
        }
    }

    /**
     * Determine headers from $_SERVER for request
     */
    abstract protected function determineHeaders();

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * // Represent the headers as a string
     * foreach ($message->getHeaders() as $name => $values) {
     * echo $name . ': ' . implode(', ', $values);
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
        if (!isset($this->headers)) {
            $this->headers = $this->determineHeaders();
        }
        
        return $this->headers;
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
        return (in_array(strtolower($name), array_change_key_case($this->getHeaders())));
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method returns an empty string.
     */
    public function getHeaderLine($name)
    {
        return join(',', $this->getHeader($name));
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
        $value = [];
        $headers = $this->getHeaders();
        $originalName = $this->getHeaderCaseSensetiveKey($name);
        if ($originalName !== false) {
            $value = $headers[$originalName];
        }
        
        return $value;
    }

    /**
     * Return Case-sensitive name of existed header. This function are uses 
     * for the getting header value and create/change header values
     *
     *  @param string $name
     *              Case-insensitive name of header
     *  @return string/float  
     */
    protected function getHeaderCaseSensetiveKey($name)
    {
        if (!$this->hasHeader($name)) {
            return false;
        }
        
        foreach ($this->getHeaders() as $k => $v) {
            if (strtolower($name) == strtolower($k)) {
                return $k;
            }
        }
        
        return false;
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
        $this->assertHeaderName($name);
        $this->assertHeaderValue($value);
        
        $request = $this->withoutHeader($name);
        $request->headers[$name] = (array)$value;
        
        return $request;
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
        $this->assertHeaderName($name);
        $this->assertHeaderValue($value);
        
        $request = clone $this;
        $oldName = $this->getHeaderCaseSensetiveKey($name);
        if ($oldName !== false) {
            $request->headers[$oldName] = array_merge($request->headers[$oldName], (array)$value);
        } else {
            $request->headers[$name] = (array)$value;
        }
        
        return $request;
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name)
    {
        $request = clone $this;
        
        if (!isset($name)) {
            return $request;
        }
        
        $oldName = $this->getHeaderCaseSensetiveKey($name);
        if (!isset($oldName)) {
            return $request;
        }
        
        unset($request->headers[$oldName]);
        
        return $request;
    }
}
