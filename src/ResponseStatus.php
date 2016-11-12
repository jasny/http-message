<?php

namespace Jasny\HttpMessage;

use Jasny\HttpMessage\Wrap;

/**
 * PSR-7 methods for http response status
 */
class ResponseStatus
{
    use Wrap\Headers;
    
    /**
     * HTTP Response status code.
     * 
     * @var int
     */
    protected $code;

    /**
     * HTTP Response status phrase
     *
     * @var string
     */
    protected $phrase;

    /**
     * State of the object: 'local', 'global' or 'stale'
     * @var string
     */
    protected $state = 'local';
    
    /**
     * HTTP protocol version
     * @var string
     */
    protected $protocolVersion;
    
    
    /**
     * Default phrases for the status codes
     * based on the RCF7231
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6
     * @var array
     */
    protected $defaultStatuses = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        426 => 'Upgrade Required',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );

    /**
     * Class constructor
     * 
     * @param string $protocolVersion
     * @param int    $code
     * @param string $reasonPhrase
     */
    public function __construct($protocolVersion, $code = null, $reasonPhrase = null)
    {
        if (!is_string($protocolVersion)) {
            throw new \InvalidArgumentException("Expected protocol version to be a string");
        }
        
        $this->protocolVersion = $protocolVersion;
        
        if (isset($code)) {
            $this->assertStatusCode($code);

            if (empty($reasonPhrase) && array_key_exists($code, $this->defaultStatuses)) {
                $reasonPhrase = $this->defaultStatuses[$code];
            }

            $this->assertReasonPhrase($reasonPhrase);

            $this->code = (int)$code;
            $this->phrase = (string)$reasonPhrase;
        }
    }
    

    /**
     * Copy the http status from the global scope
     */
    protected function copyGlobalStatus()
    {
        $code = $this->httpResponseCode() ?: null;
        
        if ($this->code === $code) {
            return;
        }
        
        $this->code = $code;
            
        if (!isset($code)) {
            $this->phrase = null;
        } elseif (array_key_exists($code, $this->defaultStatuses)) {
            $this->phrase = $this->defaultStatuses[$code];
        } else {
            $this->phrase = '';
        }
    }
    
    /**
     * Connect the response status to the global environment
     */
    public function useGlobally()
    {
        if ($this->state !== 'local') {
            return;
        }

        if (isset($this->code) && $this->code !== $this->httpResponseCode()) {
            $this->assertHeadersNotSent();
            $this->header("HTTP/{$this->protocolVersion} {$this->code} {$this->phrase}");
        }
        
        $this->state = 'global';
    }
    
    /**
     * Disconnect the response status to the global environment
     */
    public function useLocally()
    {
        if ($this->state === 'local') {
            return;
        }
        
        $this->copyGlobalStatus();
        
        $this->state = 'local';
    }
    
    /**
     * Check if the object is bound to the global environment
     * 
     * @return boolean
     */
    public function isGlobal()
    {
        return $this->state !== 'local';
    }
    
    /**
     * Mark the object as no longer in sync with the Global environment
     */
    protected function turnStale()
    {
        if ($this->state !== 'global') {
            return;
        }
        
        $this->copyGlobalStatus();
        
        $this->state = 'stale';
    }
    
    /**
     * Check if object is stale
     * 
     * @return boolean
     */
    public function isStale()
    {
        return $this->state === 'stale';
    }
    
    
    /**
     * Assert that this object isn't stale
     * 
     * @throws \RuntimeException
     */
    protected function assertNotStale()
    {
        if ($this->state === 'stale') {
            throw new \RuntimeException("Can not change stale object");
        }
    }
    
    /**
     * Assert that the status code is valid (100..999)
     *
     * @param string $code
     * @throws \InvalidArgumentException
     */
    protected function assertStatusCode($code)
    {
        if (!is_int($code)) {
            throw new \InvalidArgumentException("Response code must be integer");
        }
        
        if ($code < 100 || $code > 999) {
            throw new \InvalidArgumentException("Response code must be in range 100...999");
        }
    }

    /**
     * Function to set Status phrase
     *
     * @param string $phrase
     */
    protected function assertReasonPhrase($phrase)
    {
        if (isset($phrase) && !is_string($phrase)) {
            throw new \InvalidArgumentException("Response message must be a string");
        }
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
        if ($this->state === 'global') {
            return $this->httpResponseCode();
        }
        
        return $this->code ?: 200;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        if ($this->state === 'global') {
            $code = $this->httpResponseCode();
            
            return $this->code === $code && $this->phrase
                ? $this->phrase
                : (isset($this->defaultStatuses[$code]) ? $this->defaultStatuses[$code] : '');
        }
        
        return $this->code ? $this->phrase : $this->defaultStatuses[200];
    }

    /**
     * Set the protocol version
     * 
     * @param string $version
     * @return static
     */
    public function withProtocolVersion($version)
    {
        if (!is_string($version)) {
            throw new \InvalidArgumentException("Expected protocol version to be a string");
        }
        
        $status = clone $this;
        $status->protocolVersion = $version;
        
        return $status;
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
        $this->assertNotStale();
        $this->assertStatusCode($code);
        
        if (empty($reasonPhrase) && array_key_exists($code, $this->defaultStatuses)) {
            $reasonPhrase = $this->defaultStatuses[$code];
        }
        
        $this->assertReasonPhrase($reasonPhrase);
        
        if ($this->state === 'global') {
            $this->assertHeadersNotSent();
            $this->header("HTTP/{$this->protocolVersion} $code $reasonPhrase");
        }
        
        if ($this->code === $code && $this->phrase === $reasonPhrase) {
            return $this;
        }
        
        $status = clone $this;
        
        $this->turnStale();
        
        $status->code = (int)$code;
        $status->phrase = (string)$reasonPhrase;
        
        return $status;
    }
    
    
    /**
     * Wrapper around `header` function
     * @link http://php.net/manual/en/function.header.php
     * @codeCoverageIgnore
     * 
     * @param string $string
     */
    protected function header($string)
    {
        header($string);
    }
}