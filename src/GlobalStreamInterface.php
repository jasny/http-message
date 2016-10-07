<?php

namespace Jasny\HttpMessage;

/**
 * Interface for a stream that should behave differently when used by a response with a global
 * environment.
 */
interface GlobalStream
{
    /**
     * Called when the stream is used in a global environment
     */
    public function useGlobally();

    /**
     * Called when the stream is no longed used in a global environment
     */
    public function useLocally();
}

