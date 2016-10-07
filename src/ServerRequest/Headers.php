<?php

namespace Jasny\HttpMessage\ServerRequest;

/**
 * ServerRequest header methods
 */
trait Headers
{
    use Message\Headers;

    /**
     * Get the server parameters
     * 
     * @return array
     */
    abstract public function getServerParams();

    /**
     * Determine the headers based on the server parameters
     * 
     * @return array
     */
    protected function determineHeaders()
    {
        $headers = [];
        $params = $this->getServerParams();
        
        foreach ($params as $param => $value) {
            if (\Jasny\str_starts_with($param, 'HTTP_')) {
                $key = $this->headerCase(substr($param, 5));
            } elseif (in_array($param, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $key = $this->headerCase($param, 5);
            } else {
                continue;
            }
            
            $headers[$key] = [$value];
        }
        
        return $headers;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ': ' . implode(', ', $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * @return string[][] Returns an associative array of the message's headers.
     *     Each key is a header name, and each value is an array of strings for
     *     that header.
     */
    public function getHeaders()
    {
        if (!isset($this->headers)) {
            $this->headers = $this->determineHeaders();
        }
        
        return $this->headers;
    }
}
