<?php
namespace Jasny\HttpMessage\Response;

/**
 * ServerRequest header methods
 */
trait StatusCode
{

    /**
     * HTTP Response status code
     *
     * @var integer 3-digits from 100 to 599
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
    private $default_statuses = array(
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
     * Turn upper case param into header case.
     * (SOME_HEADER -> Some-Header)
     *
     * @param string $param            
     */
    protected function setCode($code)
    {
        if (! is_int($code)) {
            throw new \InvalidArgumentException("Response code must be integer");
        }
        
        if ($code < 100 && $code > 999) {
            throw new \InvalidArgumentException("Response code must be in range 100...999");
        }
        $this->code = $code;
    }

    /**
     * Function to set Status phrase
     *
     * @param
     *            string
     */
    protected function setReasonPhrase($phrase)
    {
        if (! is_string($phrase)) {
            throw new \InvalidArgumentException("Response message must be a string");
        }
        $this->message = $phrase;
    }

    /**
     * Function to return current setted code
     *
     * @return int(3)
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * @return int(3)
     */
    public function getReasonPhrase()
    {
        return $this->phrase;
    }
}
