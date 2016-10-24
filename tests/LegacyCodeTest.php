<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class LegacyCodeTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * @var object ServerRequest 
     */
    var $request;
    
    /**
     * @var object Response 
     */
    var $response;
    
    /**
     * @dataProvider 
     */
    public function setUp()
    {
        
    }
    
    /**
     * Global massive with returned 
     */
    public function getProvider()
    {
        /**
         * Array of params
         * @param string PATH_INFO
         * @param array Request Headers
         * @param array array of query params
         * @param array Request cookies
         * @param array Response Headers
         * @param array Response text
         */
        return [
            ['/foo', ['Foo' => 'bar'], ['page' => 1], [], ['Foo' => ['Baz']], 'Baz'],
            ['/boo', ['Foo' => 'boo'], [], ['boo', 'good', 'test'], ['Foo' => ['booPage']], 'Boo page']
        ];
    }
    
    /**
     * Emulating route class from 
     * 
     * @param object $request
     * @param object $response
     */
    public function getRoute($request, $response) 
    {
        $params = $request->getQueryParams();
        
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if ($_SERVER['PATH_INFO'] == '/foo') {
                if ($_GET['page'] == '1') {
                    header('Foo: Baz');
                    echo 'Baz';
                }
            }
        
            if ($_SERVER['PATH_INFO'] == '/boo') {
                header('Foo: booPage');
                echo 'Boo page';
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if($_SERVER['PATH_INFO'] == '/post') {
                if (!empty($_POST)) {
                    if ($_POST['name'] == 'Jasny') {
                        header('Status: ok');
                        echo 'Hello Jasny';
                    }
                }
            }
            if($_SERVER['PATH_INFO'] == '/login') {
                if (!empty($_POST)) {
                    if ($_POST['name'] == 'Jasny' && $_POST['password'] == 'Http') {
                        header('Page: Login');
                        header('Login: Ok');
                        echo 'You are logged in!';
                    }
                }
            }
        }
    }
    
    /**
     * @dataProvider getProvider
     */
    public function testGet($uri, $requestHeaders, $query, $cookies, $responseHeaders, $output)
    {
        ob_start();
        
        $request = (new ServerRequest())->withGlobalEnvironment()
            ->withServerParams(['REQUEST_METHOD' => 'GET', 'PATH_INFO' => $uri]);
        if (!empty($requestHeaders)) {
            foreach ($requestHeaders as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }
        if (!empty($query)) {
            $request = $request->withQueryParams($query);
        }
        if (!empty($cookies)) {
            $request = $request->withCookieParams($query);
        }
        
        $response = (new Response())->withGlobalEnvironment();
        
        $this->getRoute($request, $response);
        
        $response = $response->withoutGlobalEnvironment();
        
        header_remove();
        ob_end_clean();
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertInstanceof(Response::class, $response);
        
        $this->assertEquals('GET', $request->getMethod());
        
        foreach ($responseHeaders as $name => $value) {
            $this->assertEquals($value, $response->getHeader($name));
            $this->assertEquals(implode(', ', $value), $response->getHeaderLine($name));
            
        }
        $this->assertEquals(strlen($output), $response->getBody()->tell());
        $response->getBody()->rewind();
        $this->assertEquals($output, $response->getBody()->getContents());
        $this->assertEquals(strlen($output), $response->getBody()->getSize());
    }
    

    /**
     * Global massive with returned
     */
    public function postProvider()
    {
        /**
         * Array of params
         * @param string PATH_INFO
         * @param array Post array
         * @param array Request Headers
         * @param array array of query params
         * @param array Request cookies
         * @param array Response Headers
         * @param array Response text
         */
        return [
            ['/post', ['name' => 'Jasny'],  [], [], [], ['Status' => ['ok']], 'Hello Jasny'],
            ['/login', ['name' => 'Jasny', 'password' => 'Http'], [], [], ['boo', 'good', 'test'], ['Login' => ['Ok'], 'Page' => ['Login']], 'You are logged in!']
        ];
    }
    
    /**
     * @dataProvider postProvider
     */
    public function testPost($uri, $post, $requestHeaders, $query, $cookies, $responseHeaders, $output)
    {
        ob_start();
        
        $_POST = $post;
        $request = (new ServerRequest())->withGlobalEnvironment()
            ->withServerParams(['REQUEST_METHOD' => 'POST', 'PATH_INFO' => $uri]);
        if (!empty($requestHeaders)) {
            foreach ($requestHeaders as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }
        if (!empty($query)) {
            $request = $request->withQueryParams($query);
        }
        if (!empty($cookies)) {
            $request = $request->withCookieParams($query);
        }
        
        $response = (new Response())->withGlobalEnvironment();
        
        $this->getRoute($request, $response);
        
        $response = $response->withoutGlobalEnvironment();
        
        header_remove();
        ob_end_clean();
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertInstanceof(Response::class, $response);
        
        $this->assertEquals('POST', $request->getMethod());
        
        foreach ($responseHeaders as $name => $value) {
            $this->assertEquals($value, $response->getHeader($name));
            $this->assertEquals(implode(', ', $value), $response->getHeaderLine($name));
            
        }
        $this->assertEquals(strlen($output), $response->getBody()->tell());
        $response->getBody()->rewind();
        $this->assertEquals($output, $response->getBody()->getContents());
        $this->assertEquals(strlen($output), $response->getBody()->getSize());
    }
}