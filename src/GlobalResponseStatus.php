<?php

namespace Jasny\HttpMessage;

use Jasny\HttpMessage\ResponseStatus;
use Jasny\HttpMessage\Wrap;

/**
 * PSR-7 methods for http response status that binds to the global environment
 */
class GlobalResponseStatus extends ResponseStatus
{
    use Wrap\Headers;
    
    /**
     * HTTP protocol version
     * @var string
     */
    protected $protocolVersion = '1.1';
    
    
    /**
     * Set the protocol version
     * 
     * @param string $version
     * @return $this
     */
    public function withProtocolVersion($version)
    {
        if (!is_string($version)) {
            throw new \InvalidArgumentException("Expected protocol version to be a string");
        }
        
        $this->protocolVersion = $version;
        
        $this->sendStatusHeader();
        
        return $this;
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
        return $this->httpResponseCode() ?: 200;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        $code = $this->getStatusCode();

        return $this->code === $code && $this->phrase
            ? $this->phrase
            : (isset($this->defaultStatuses[$code]) ? $this->defaultStatuses[$code] : '');
    }

    
    /**
     * Set the specified status code and reason phrase.
     * 
     * @param int    $code
     * @param string $reasonPhrase
     */
    protected function setStatus($code, $reasonPhrase)
    {
        $this->assertHeadersNotSent();

        parent::setStatus($code, $reasonPhrase);
        
        $this->sendStatusHeader();
    }
    
    /**
     * Create a new response status object with the specified code and phrase.
     * 
     * @param int     $code
     * @param string  $reasonPhrase
     * @return $this
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $this->setStatus($code, $reasonPhrase);
        
        return $this;
    }
    
    
    /**
     * Send the HTTP header to set the HTTP response.
     */
    protected function sendStatusHeader()
    {
        $code = parent::getStatusCode();
        $phrase = parent::getReasonPhrase();
        
        $this->header("HTTP/{$this->protocolVersion} {$code} {$phrase}");
    }
}
