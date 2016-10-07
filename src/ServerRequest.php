<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ServerRequestInterface;
use Jasny\HttpMessage\ServerRequest;

/**
 * Representation of an incoming, server-side HTTP request.
 */
class ServerRequest implements ServerRequestInterface
{
    use ServerRequest\GlobalEnvironment;
    use ServerRequest\ServerParams;
    use ServerRequest\ProtocolVersion;
    use ServerRequest\Headers;
    use ServerRequest\Body;
    use ServerRequest\RequestTarget;
    use ServerRequest\Method;
    use ServerRequest\Uri;
    use ServerRequest\Cookies;
    use ServerRequest\QueryParams;
    use ServerRequest\UploadedFiles;
    use ServerRequest\ParsedBody;
    use ServerRequest\Attributes;
    
    
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->createDerivedAttributes();
    }
    
    /**
     * Remove all set and cached values
     */
    protected function reset()
    {
        $this->protocolVersion = null;
        $this->headers = null;
        $this->requestTarget = null;
        $this->method = null;
        $this->uri = null;
    }
}
