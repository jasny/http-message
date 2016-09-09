<?php

namespace Jasny\HttpMessage;

/**
 * Overwrite build-in function move_uploaded_file
 * @link http://php.net/manual/en/function.move-uploaded-file.php
 * @ignore
 * 
 * @param string $filename
 * @param string $destination
 * @return boolean
 */
function move_uploaded_file($filename, $destination)
{
    return rename($filename, $destination);
}
