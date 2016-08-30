<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ServerRequestInterface;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\Stream;

/**
 * Representation of an incoming, server-side HTTP request.
 */
class ServerRequest implements ServerRequestInterface
{
    use ServerRequest\ProtocolVersion;
    use ServerRequest\Headers;
    use ServerRequest\Body;
    use ServerRequest\RequestTarget;
    use ServerRequest\Method;
    use ServerRequest\Uri;
    use ServerRequest\ServerParams;
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
     * Use super globals $_SERVER, $_COOKIE, $_GET, $_POST and $_FILES and the php://input stream.
     * 
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @return self
     */
    public function withSuperGlobals()
    {
        $request = clone $this;
        
        $request->serverParams =& $_SERVER;
        $request->cookies =& $_COOKIE;
        $request->queryParams =& $_GET;
        
        $request->postData =& $_POST;
        $request->parsedBodyStats = null;
        $request->setUploadedFiles($_FILES);
        $request->body = new Stream(fopen('php://input', 'r'));
        
        return $request;
    }
}
