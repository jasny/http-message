<?php

namespace Jasny\HttpMessage\ServerRequest;

use Jasny\HttpMessage\Stream;

/**
 * ServerRequest methods for using the global enviroment
 */
trait GlobalEnvironment
{
    /**
     * The object is stale if it no longer reflects the global enviroment
     * @var boolean|null
     */
    protected $isStale;
    
    
    /**
     * Remove referencing from properties
     * 
     * @param string[] $properties
     */
    protected function dereferenceProperty(...$properties)
    {
        foreach ((array)$properties as $property) {
            if (!property_exists($this, $property)) {
                continue;
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
     * @param boolean $byReference  Set the supoerglobals by reference
     * @return self
     * @throws RuntimeException if isn't not possible to open the 'php://input' stream
     */
    public function withGlobalEnvironment($byReference = true)
    {
        $request = clone $this;
        
        $request->serverParams =& $_SERVER;
        $request->cookies =& $_COOKIE;
        $request->queryParams =& $_GET;
        
        $request->setPostData($_POST);
        $request->setUploadedFiles($_FILES);
        
        $request->body = Stream::open('php://input', 'r');
        
        $request->reset();
        
        if ($byReference) {
            $request->isStale = false;
        } else {
            $request->turnStale();
            $request->isStale = null;
        }
        
        return $request;
    }
    
    /**
     * Return object that is disconnected from superglobals
     * 
     * @return self
     */
    public function withoutGlobalEnvironment()
    {
        if ($this->isStale !== false) {
            return $this;
        }
        
        $request = clone $this;
        
        $request->turnStale();
        $request->isStale = null;
        
        return $request;
    }
    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    protected function turnStale()
    {
        $request = clone $this;
        
        if ($this->isStale === false) {
            $this->dereferenceProperty('serverParams', 'cookies', 'queryParams', 'parsedBody', 'uploadedFiles');
            $this->isStale = true;
        }
        
        return $request;
    }
    
    /**
     * The object is stale if it no longer reflects the global enviroment.
     * Returns null if the object isn't using the globla state.
     * 
     * @var boolean|null
     */
    public function isStale()
    {
        return $this->isStale;
    }
}
