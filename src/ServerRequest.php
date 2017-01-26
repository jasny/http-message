<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ServerRequestInterface;
use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\GlobalEnvironmentInterface;

/**
 * Representation of an incoming, server-side HTTP request.
 */
class ServerRequest implements ServerRequestInterface, GlobalEnvironmentInterface
{
    use ServerRequest\ServerParams;
    use ServerRequest\ProtocolVersion;
    use ServerRequest\Headers;
    use ServerRequest\Body;
    use ServerRequest\RequestTarget;
    use ServerRequest\Method;
    use ServerRequest\Uri;
    use ServerRequest\CookieParams;
    use ServerRequest\QueryParams;
    use ServerRequest\UploadedFiles;
    use ServerRequest\ParsedBody;
    use ServerRequest\Attributes;
    
    
    /**
     * The object is stale if it no longer reflects the global enviroment
     * @var boolean|null
     */
    protected $isStale;
    
    
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
    
    /**
     * Remove referencing from properties
     * 
     * @param string[] $properties
     */
    protected function dereferenceProperty(...$properties)
    {
        foreach ((array)$properties as $property) {
            if (!property_exists($this, $property)) {
                continue; // @codeCoverageIgnore
            }
            
            $value = $this->$property;
            unset($this->$property);
            $this->$property = $value;
        }
    }
    
    
    /**
     * Use superglobals $_SERVER, $_COOKIE, $_GET, $_POST and $_FILES and the php://input stream.
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @param boolean $bind  Bind server request to global environment
     * @return ServerRequest
     * @throws RuntimeException if isn't not possible to open the 'php://input' stream
     */
    public function withGlobalEnvironment($bind = false)
    {
        if ($this->isStale) {
            throw new \BadMethodCallException("Unable to use a stale server request. Did you mean to rivive it?");
        }
        
        if ($this->isStale === false) {
            return $this->copy();
        }
        
        $request = $this->buildGlobalEnvironment();
        
        if (!$bind) {
            $request->copy();
            $request->isStale = null;
        }
        
        return $request;
    }
    
    /**
     * Build the global environment
     */
    protected function buildGlobalEnvironment()
    {
        $request = clone $this;
        
        $request->serverParams =& $_SERVER;
        $request->cookies =& $_COOKIE;
        $request->queryParams =& $_GET;
        
        $request->setPostData($_POST);
        $request->setUploadedFiles($_FILES);
        
        $request->body = Stream::open('php://input', 'r');
        
        $request->reset();
        
        $request->isStale = false;
        
        return $request;
    }
    
    /**
     * Return object that is disconnected from superglobals
     * 
     * @return ServerRequest
     */
    public function withoutGlobalEnvironment()
    {
        if ($this->isStale !== false) {
            return $this;
        }
        
        $request = clone $this;
        
        $request->copy();
        $request->isStale = null;
        
        return $request;
    }
    
    
    /**
     * The object is stale if it no longer reflects the global environment.
     * Returns null if the object isn't using the global state.
     * 
     * @return boolean|null
     */
    public function isStale()
    {
        return $this->isStale;
    }
    
    /**
     * Clone the server request.
     * Turn stale if the request is bound to the global environment.
     * 
     * @return ServerRequest  A non-stale request
     * @throws \BadMethodCallException when the request is stale
     */
    protected function copy()
    {
        if ($this->isStale) {
            throw new \BadMethodCallException("Unable to modify a stale server request object");
        }
        
        $request = clone $this;
        
        if ($this->isStale === false) {
            $this->dereferenceProperty('serverParams', 'cookies', 'queryParams', 'postData', 'uploadedFiles');
            $this->isStale = true;
        }
        
        return $request;
    }
    
    /**
     * Revive a stale server request
     * 
     * @return ServerRequest
     */
    public function revive()
    {
        if ($this->isStale !== true) {
            return $this;
        }
        
        $request = $this->buildGlobalEnvironment();
        
        return $request
            ->withServerParams($this->getServerParams())
            ->withCookieParams($this->getCookieParams())
            ->withQueryParams($this->getQueryParams())
            ->withParsedBody($this->getParsedBody())
            ->withBody(clone $this->getBody())
            ->withUploadedFiles($this->getUploadedFiles());
    }
}
