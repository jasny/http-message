<?php

namespace Jasny\HttpMessage;

/**
 * @var array $headers_list
 */
$headersList = [];
$ResponseCode = 200;
/**
 * Overwrite build-in function headers
 * @link http://php.net/manual/en/function.header.php
 * @ignore
 * 
 * @param string $string
 * @param bool $string
 * @param int $response code
 * @return void
 */
function header($string, $replace = false, $http_response_code = 0)
{
    global $headersList, $ResponseCode; 
    list($key, $value) = explode(': ', $string);
    if (isset($headersList[strtolower($key)]) && !$replace){
        $headersList[strtolower($key)] .= $value;
    } else {
        $headersList[strtolower($key)] = $string;
    }
    
    if ($http_response_code) {
        $ResponseCode = $http_response_code;
    }
}

/**
 * Overwrite build-in function headers_list
 * @link http://php.net/manual/en/function.headers-list.php
 * @ignore
 * 
 * @return array
 */
function headers_list()
{
    global $headersList;
    
    $return = [];
    foreach ($headersList as $value) {
        $return[] = $value;
    }
    return $return;
}

/**
 * Overwrite build-in function header_remove
 * @link http://php.net/manual/en/function.header-remove.php
 * @ignore
 * 
 * @param string $name
 * @return void
 */
function header_remove($name = '')
{
    global $headersList; 
    if ($name != ''){
        if (isset($headersList[strtolower($name)])) {
            unset($headersList[strtolower($name)]);
        }
    } else {
        $headersList = [];
    }
}
