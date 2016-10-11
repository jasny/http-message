<?php

namespace Jasny\HttpMessage;

use Jasny\HttpMessage\Headers\HeadersInterface;

/**
 * ServerRequest header methods
 */
class ResponseHeaders extends Headers implements HeadersInterface
{
    
    /**
     * HTTP headers
     *
     * @var array
     */
    protected $isStale;

    /**
     * For the current object simple chek  if current object 
     * can be used for set or modify or remove headers
     *
     * @throws \InvalidArgumentException
     */
    protected function assertStale()
    {
        if ($this->isStale !== null) {
            throw new \InvalidArgumentException("Can not change stale object");
        }
    }

    
    /**
     * 
     * @param unknown $headers
     */
    protected function setClassStale()
    {
        $this->isStale = true;
        
        $list = headers_list();
        foreach ($list as $header) {
            list($key, $value) = explode(': ', $header);
            $headers[strtolower($key)] = ['k' => $key, 'v' => explode(', ', $value)];
        }
    }

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
        $headers = [];
        
        if ($this->isStale) {
            return parent::getHeaders();
        }
        
        $list = headers_list();
        foreach ($list as $header) {
            list($key, $value) = explode(': ', $header);
            $headers[$key] = explode(', ', $value);
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
        $this->assertHeaderName($name);
        
        if ($this->isStale) {
            return parent::getHeaders($name);
        }
        
        $headers = headers_list();
        foreach ($headers as $header) {
            list($key, $value) = explode(': ', $header);
            if (strtolower($key) == strtolower($name)) {
                return true;
            }
        }
        return false;
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
        $this->assertHeaderName($name);
        
        if ($this->isStale){
            return parent::getHeader($name);
        }
        
        $headers = \headers_list();
        foreach ($headers as $header) {
            list($key, $value) = explode(': ', $header);
            if (strtolower($key) == strtolower($name)) {
                return $value;
            }
        }
        return '';
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
        $this->assertHeaderName($name);
        
        if ($this->isStale) {
           return parent::getHeader($name);
        }
        
        $headers = headers_list();
        return $headers;
        foreach ($headers as $header) {
            list($key, $value) = explode(': ', $header);
            if (strtolower($key) == strtolower($name)) {
                return explode(', ', $value);
            }
        }
        return [];
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
        $this->assertStale();
        
        $request = clone $this;
        $this->setClassStale();
        
        header($name . ': ' . implode(', ', (array)$value));
        
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
        $this->assertStale();
        
        $request = clone $this;
        $this->setClassStale();
        
        if (isset($request->headers[strtolower($name)])) {
            array_push($request->headers[strtolower($name)]['v'], $value);
            header_remove($name);
            header($name . ': ' . $request->getHeaderLine($name));
        } else {
            $request->headers[strtolower($name)] = ['k' => $name, 'v' => (array)$value];
            header($name . ': ' . $request->getHeaderLine($name));
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
        $this->assertHeaderName($name);
        $this->assertStale();
        
        if (!isset($this->headers[strtolower($name)])) {
            return $this;
        }
        $request = clone $this;
        $this->setClassStale();
        unset($request->headers[strtolower($name)]);
        header_remove($name);
        
        return $request;
    }
}
