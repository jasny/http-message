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
     * Use super globals $_SERVER, $_COOKIE, $_GET, $_POST and $_FILES and the php://input stream.
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @global $_SERVER
     * @global $_COOKIE
     * @global $_GET
     * @global $_POST
     * @global $_FILES
     * 
     * @return self
     * @throws RuntimeException if isn't not possible to open the 'php://input' stream
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
        
        $request->body = Stream::open('php://input', 'r');
        
        $request->reset();
        
        return $request;
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
