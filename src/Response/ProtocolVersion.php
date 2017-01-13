<?php

namespace Jasny\HttpMessage\Response;

use Jasny\HttpMessage\Message;

/**
 * ServerRequest protocol version methods
 */
trait ProtocolVersion
{
    use Message\ProtocolVersion;

    /**
     * Determine the protocol versions based on the server params.
     * 
     * @return string
     */
    protected function determineProtocolVersion()
    {
        return "1.1";
    }
}
