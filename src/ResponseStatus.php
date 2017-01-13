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
     * @param int|ResponseStatus|null $code
     * @param string                  $reasonPhrase
     */
    public function __construct($code = null, $reasonPhrase = '')
    {
        if ($code instanceof self) {
            $reasonPhrase = $code->getReasonPhrase();
            $code = $code->getStatusCode();
        }
        
        if (isset($code)) {
            $this->setStatus($code, $reasonPhrase);
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
        if (!is_int($code) && !(is_string($code) && ctype_digit($code))) {
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
     * Set the specified status code and reason phrase.
     * 
     * @param int    $code
     * @param string $reasonPhrase
     */
    protected function setStatus($code, $reasonPhrase)
    {
        $this->assertStatusCode($code);
        $this->assertReasonPhrase($reasonPhrase);
        
        if (empty($reasonPhrase) && array_key_exists($code, $this->defaultStatuses)) {
            $reasonPhrase = $this->defaultStatuses[$code];
        }
        
        $this->code = (int)$code;
        $this->phrase = (string)$reasonPhrase;
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
        return $this->code ?: 200;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        return !empty($this->code) ? $this->phrase : $this->defaultStatuses[200];
    }

    
    /**
     * Create a new response status object with the specified code and phrase.
     * 
     * @param int     $code
     * @param string  $reasonPhrase
     * @return ResponseStatus
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $status = clone $this;
        $status->setStatus($code, $reasonPhrase);
        
        return $status;
    }
}
