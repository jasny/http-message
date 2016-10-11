<?php

namespace Jasny\HttpMessage\Response;

use Jasny\HttpMessage\Message;

/**
 * ServerRequest header methods
 */
trait Headers
{
    use Message\Headers;

    /**
     * Determine the headers based on the server parameters
     * 
     * @return array headers array with structure $key => array $value 
     */
    protected function determineHeaders()
    {
        return [];
    }
}
