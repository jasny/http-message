<?php

namespace Jasny\HttpMessage\Response;

use Jasny\HttpMessage\Response;
use Jasny\HttpMessage\Headers;
use Jasny\HttpMessage\HeadersInterface;
use Jasny\HttpMessage\ResponseHeaders;
use Jasny\HttpMessage\ResponseStatus;
use Jasny\HttpMessage\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * ServerRequest methods for using the global enviroment
 */
trait GlobalEnvironment
{
    /**
     * Get or set HTTP headers object
     * 
     * @param HeadersInterface $headers
     * @return HeadersInterface
     */
    abstract protected function headersObject(HeadersInterface $headers = null);
    
    /**
     * Get or set HTTP Response status
     * 
     * @param ResponseStatus $status
     * @return ResponseStatus
     */
    abstract protected function statusObject(ResponseStatus $status = null);
    
    
    /**
     * Get the body
     * @return StreamInterface
     */
    abstract public function getBody();
    
    /**
     * Get the headers
     * @return string[][]
     */
    abstract public function getHeaders();
    
    /**
     * Function for the protocol version
     * @return string
     */
    abstract public function getProtocolVersion();
}
