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
     * Determine the headers based on the server parameters
     * 
     * @return array headers array with structure $key => [$value]
     */
    protected function determineHeaders()
    {
        $params = $this->getServerParams();
        $headers = [];
        
        foreach ($params as $param => $value) {
            if (\Jasny\str_starts_with($param, 'HTTP_')) {
                $key = $this->headerCase(substr($param, 5));
            } elseif (in_array($param, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $key = $this->headerCase($param);
            } else {
                continue;
            }
            
            $headers[$key] = [$value];
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
