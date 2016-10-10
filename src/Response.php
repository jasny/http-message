<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ResponseInterface;
use Jasny\HttpMessage\Response;

/**
 * Http response
 */
class Response implements ResponseInterface
{
    use Response\ProtocolVersion;
    use Response\StatusCode;
    use Response\Headers;
    use Response\Body;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->initHeaders();
    }
} 
