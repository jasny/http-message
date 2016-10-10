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
     * @return array
     */
    protected function determineHeaders()
    {
        $params = $this->getServerParams();
        $headers = [];
        foreach ($params as $param => $value) {
            if (\Jasny\str_starts_with($param, 'HTTP_')) {
                $key = substr($param, 5);
            } elseif (in_array($param, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $key = $param;
            } else {
                continue;
            }
            
            $headers[$key] = [$value];
        }
        
        return $headers;
    }
}
