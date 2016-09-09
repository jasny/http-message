<?php

namespace Jasny\HttpMessage;

/**
 * Overwrite build-in function is_uploaded_file
 * @link http://php.net/manual/en/function.is-uploaded-file.php
 * @ignore
 * 
 * @param string $filename
 * @return boolean
 */
function is_uploaded_file($filename)
{
    return dirname($filename) === 'vfs://root/tmp';
}
