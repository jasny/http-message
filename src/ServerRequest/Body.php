<?php

namespace Jasny\HttpMessage\ServerRequest;

/**
 * ServerRequest body methods
 */
trait Body
{
    use Message\Body;
    /**
     *
     * @var string
     */
    protected $defaultStream = 'data://text/plain,';
    /**
     *
     * @var string
     */
    protected $defaultMode = 'r';

    /**
     * Reset the parsed body, excepted if it was explicitly set
     */
    abstract protected function resetParsedBody();

    /**
     * Set the body
     *
     * @param StreamInterface $body
     */
    protected function setBody(StreamInterface $body)
    {
        $this->body = $body;
        $this->resetParsedBody();
    }
}
