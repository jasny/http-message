<?php
namespace Jasny\HttpMessage;

use Psr\Http\Message\ResponseInterface; 
use Jasny\HttpMessage\Response;

/**
 * Http response
 */
class Response  implements ResponseInterface
{
    use Message\ProtocolVersion;
    use Response\StatusCode;
    use Message\Headers;
    use Message\Body;
    use Response\Body;

    /**
     * Class constructor
     */
    public function __construct()
    {}
}
