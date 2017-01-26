<?php

namespace Jasny\HttpMessage\ServerRequest;

use Jasny\HttpMessage\Message;

/**
 * ServerRequest header methods
 */
trait Headers
{
    use Message\Headers;

    
    /**
     * Get the server parameters
     * 
     * @return array
     */
    abstract public function getServerParams();
    
    
    /**
     * Turn a server parameter key to a header name
     * 
     * @param string $key
     * @return string|null
     */
    protected function serverParamKeyToHeaderName($key)
    {
        $name = null;
        
        if (\Jasny\str_starts_with($key, 'HTTP_')) {
            $name = $this->headerCase(substr($key, 5));
        } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
            $name = $this->headerCase($key);
        }
            
        return $name;
    }

    /**
     * Determine the headers based on the server parameters
     * 
     * @return array headers array with structure $key => [$value]
     */
    protected function determineHeaders()
    {
        $params = $this->getServerParams();
        $headers = [];
        
        foreach ($params as $key => $value) {
            $name = $this->serverParamKeyToHeaderName($key);
            
            if (isset($name) && is_string($value)) {
                $headers[$name] = [$value];
            }
        }
        
        return $headers;
    }
    
    /**
     * Turn upper case param into header case.
     * (SOME_HEADER -> Some-Header)
     * 
     * @param string $param
     * @return string
     */
    protected function headerCase($param)
    {
        $sentence = preg_replace('/[\W_]+/', ' ', $param);
        return str_replace(' ', '-', ucwords(strtolower($sentence)));
    }
}
