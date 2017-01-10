<?php

namespace Jasny\HttpMessage\Wrap;

/**
 * Wrap PHP output control functions
 * @link http://php.net/manual/en/ref.outcontrol.php
 * @codeCoverageIgnore
 */
trait OutputControl
{
    /**
     * Wrapper around `ob_get_level()`
     * 
     * @return int
     */
    protected function obGetLevel()
    {
        return ob_get_level();
    }
    
    /**
     * Wrapper around `ob_get_contents()`
     * 
     * @return string
     */
    protected function obGetContents()
    {
        return ob_get_contents();
    }
    
    /**
     * Wrapper around `ob_clean()`
     * 
     * @return string
     */
    protected function obClean()
    {
        return ob_clean();
    }
    
    /**
     * Wrapper around `ob_flush()`
     */
    protected function obFlush()
    {
        ob_flush();
    }
    
    /**
     * Wrapper around `ob_get_length()`
     */
    protected function obGetLength()
    {
        return ob_get_length();
    }
}
