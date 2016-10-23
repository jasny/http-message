<?php

namespace Jasny\HttpMessage\Response;

use Jasny\HttpMessage\Stream;
use Jasny\HttpMessage\Headers as HeaderClass;
use Jasny\HttpMessage\ResponseHeaders;

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
     * The object is stale if it no longer reflects the global enviroment
     * @var object|array|null
     */
    protected $headers;
    
    /**
     * Function from Body trait
     * @return object
     */
    abstract public function getBody();
    /**
     * Function from Headers trait
     * @return array
     */
    abstract public function getHeaders();
    
    /**
     * Use php://output stream and default php functions work with headers.
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @return self
     * @throws RuntimeException if isn't not possible to open the 'php://output' stream
     */
    public function withGlobalEnvironment()
    {
        $response = $this->turnStale();
        $response->getBody()->useGlobally();
        $response->headers = new ResponseHeaders();
        
        return $response;
    }
    

    /**
     * Return object that is disconnected from superglobals
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @return self
     */
    public function withoutGlobalEnvironment()
    {
        $response = $this->turnStale();
        $response->getBody()->useLocally();
        $response->headers = new HeaderClass($this->headers);
        
        return $response;
    }
    
    /**
     * Disconnect the global enviroment, turning stale
     * Headers object should be replaced by a normal array.
     * 
     * @return self  Clone of non-stale request
     */
    protected function turnStale()
    {
        $response = clone $this;
        $this->isStale = true;
        $this->headers = $this->getHeaders();
        
        return $response;
    }
    
    /**
     * The object is stale if it no longer reflects the global enviroment.
     * Returns null if the object isn't using the globla state.
     * 
     * @return boolean If current object are stale 
     */
    public function isStale()
    {
        return $this->isStale;
    }
}
