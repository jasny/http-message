<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;

/**
 * @covers Jasny\HttpMessage\ResponseHeaders
 * @runTestsInSeparateProcesses
 */
class LegacyCodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var object Mock object for route 
     */
    var $router;

    public function setUp()
    {
        $this->router = new TestRoute();
    }

    public function testFoo()
    {
        ob_start();
        
        $request = (new ServerRequest())->withGlobalEnvironment()
            ->withServerParams(['REQUEST_METHOD' => 'GET', 'PATH_INFO' => '/foo'])
            ->withQueryParams(['page' => 1]);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        
        $response = (new Response())->withGlobalEnvironment();
        $this->assertInstanceof(Response::class, $response);
        
        $this->router->route($request, $response);
        
        $this->assertInstanceof(Response::class, $response);
        $response = $response->withoutGlobalEnvironment();
        
        header_remove();
        ob_end_clean();
        $this->assertEquals(['baz'], $response->getHeader('Foo'));
        $this->assertEquals('baz', $response->getHeaderLine('Foo'));
        $this->assertEquals(3, $response->getBody()->tell());
        $response->getBody()->rewind();
        $this->assertEquals('Baz', $response->getBody()->getContents());
        $this->assertEquals(3, $response->getBody()->getSize());
    }

    public function testSecondPage()
    {
        ob_start();
        
        $request = (new ServerRequest())->withGlobalEnvironment()
            ->withServerParams(['REQUEST_METHOD' => 'GET', 'PATH_INFO' => '/foo'])
            ->withQueryParams(['page' => 2]);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $response = (new Response())->withGlobalEnvironment();
        $this->assertInstanceof(Response::class, $response);
        
        $this->router->route($request, $response);
        
        $this->assertInstanceof(Response::class, $response);
        $response = $response->withoutGlobalEnvironment();
        
        header_remove();
        ob_end_clean();
        $this->assertEquals(['baz'], $response->getHeader('Foo'));
        $this->assertEquals('baz', $response->getHeaderLine('Foo'));
        $this->assertEquals(11, $response->getBody()->tell());
        $response->getBody()->rewind();
        $this->assertEquals('Second page', $response->getBody()->getContents());
        $this->assertEquals(11, $response->getBody()->getSize());
    }

    public function testDifferentUri()
    {
        ob_start();
        
        $request = (new ServerRequest())
            ->withGlobalEnvironment()
            ->withServerParams(['REQUEST_METHOD' => 'GET', 'PATH_INFO' => '/bar']);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $response = (new Response())->withGlobalEnvironment();
        $this->assertInstanceof(Response::class, $response);
        
        $this->router->route($request, $response);
        
        $this->assertInstanceof(Response::class, $response);
        $response = $response->withoutGlobalEnvironment();
        
        header_remove();
        ob_end_clean();
        
        $this->assertEquals([], $request->getQueryParams());
        $this->assertEquals(['foo', 'boo'], $response->getHeader('Bar'));
        $this->assertEquals('foo, boo', $response->getHeaderLine('Bar'));
        $this->assertEquals(13, $response->getBody()->tell());
        $response->getBody()->rewind();
        $this->assertEquals('Some bar page', $response->getBody()->getContents());
        $this->assertEquals(13, $response->getBody()->getSize());
    }
}

class TestRoute
{
    /**
     * General function to emulate site routing class 
     */
    public function route($request, $response)
    {
        $params = $request->getQueryParams();
        
        if ($request->getMethod() == 'GET') {
            if ($request->getUri() == '/foo') {
                if ($params['page'] == 1) {
                    header('Foo: baz');
                    echo 'Baz';
                }
                if ($params['page'] == 2) {
                    header('Foo: baz');
                    echo 'Second page';
                }
            }
            
            if ($request->getUri() == '/bar') {
                header('Bar: foo, boo');
                echo 'Some bar page';
            }
        }
    }
}
