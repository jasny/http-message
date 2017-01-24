<?php

namespace Jasny\HttpMessage;

/**
 * Interface that ServerRequest or Response can interact with the global environment
 */
interface GlobalEnvironmentInterface
{
    /**
     * Return object that is synced witht the global environment.
     * 
     * @param boolean $bind  Bind server request to global environment
     * @return static
     */
    public function withGlobalEnvironment($bind = false);
    
    /**
     * Return object that is disconnected from the global environment
     * 
     * @return static
     */
    public function withoutGlobalEnvironment();
    
    /**
     * The object is stale if it no longer reflects the global environment.
     * Returns null if the object isn't using the global state.
     * 
     * @return boolean|null
     */
    public function isStale();
    
    /**
     * Revive a stale object
     * 
     * @return static
     */
    public function revive();
}
