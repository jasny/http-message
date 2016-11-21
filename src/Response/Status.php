<?php

namespace Jasny\HttpMessage\Response;

use Jasny\HttpMessage\ResponseStatus;

/**
 * ServerRequest header methods
 */
trait Status
{
    /**
     * @var ResponseStatus 
     */
    protected $status;
    
    /**
     * Function for the protocol version
     * @return string
     */
    abstract public function getProtocolVersion();

    
    /**
     * Get or set HTTP Response status
     * 
     * @param ResponseStatus $status
     * @return ResponseStatus
     */
    final protected function statusObject(ResponseStatus $status = null)
    {
        if (func_num_args() >= 1) {
            $this->status = $status;
        }
        
        return $this->status;
    }
    
    
    /**
     * @return ResponseStatus
     */
    protected function getStatus()
    {
        if (!isset($this->status)) {
            $this->status = new ResponseStatus($this->getProtocolVersion());
        }
        
        return $this->status;
    }

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->getStatus()->getStatusCode();
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->getStatus()->getReasonPhrase();
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code
     *            The 3-digit integer result code to set.
     * @param string $reasonPhrase
     *            The reason phrase to use with the
     *            provided status code; if none is provided, implementations MAY
     *            use the defaults as suggested in the HTTP specification.
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $status = $this->getStatus()->withStatus($code, $reasonPhrase);
        
        $response = clone $this;
        $response->status = $status;
        
        return $response;
    }
}
