<?php

namespace Jasny\HttpMessage;

/**
 * Description of ResponseStatus
 *
 * @author arnold
 */
class ResponseStatus
{
    /**
     * HTTP Response status code.
     * 
     * @var int
     */
    protected $code = 200;

    /**
     * HTTP Response status phrase
     *
     * @var string
     */
    protected $phrase = 'OK';

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
     * @param int    $code
     * @param string $reasonPhrase
     */
    public function __construct($code = null, $reasonPhrase = null)
    {
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
        return $this->code;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->phrase;
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
        $this->assertStatusCode($code);
        
        if (empty($reasonPhrase) && array_key_exists($code, $this->defaultStatuses)) {
            $reasonPhrase = $this->defaultStatuses[$code];
        }
        
        $this->assertReasonPhrase($reasonPhrase);
        
        $status = clone $this;
        
        $status->code = (int)$code;
        $status->phrase = (string)$reasonPhrase;

        return $status;
    }
}