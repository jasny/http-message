<?php

namespace Jasny\HttpMessage;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\UploadedFile;
use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @runTestsInSeparateProcesses
 * @coversNothing
 */
class LegacyCodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $root;
    
    /**
     * @var array
     */
    protected $globals;
    
    public function setUp()
    {
        $structure = [
            'ab' => 'hello',
            'cd' => 'how are',
            'ef' => '<b>you</b>',
        ];
        
        $this->root = vfsStream::setup('tmp', null, $structure);
        
        $this->globals = [
            'get' => $_GET,
            'post' => $_POST,
            'cookie' => $_COOKIE,
            'server' => $_SERVER
        ];
        
        ob_start();
    }
    
    public function tearDown()
    {
        ob_end_clean();
    }


    /**
     * Initialize the request
     * 
     * @param ServerRequest $request
     * @return ServerRequest
     */
    protected function initRequest(ServerRequest $request)
    {
        return $request
            ->withServerParams([
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'PATH_INFO' => '/foo'
            ])
            ->withQueryParams(['full' => 1, 'mark' => 'pop'])
            ->withCookieParams(['foo' => 'bar'])
            ->withParsedBody(['name' => 'John', 'email' => 'john@example.com'])
            ->withUploadedFiles([
                'file' => new UploadedFile([
                    'name' => 'foo.txt',
                    'type' => 'text/plain',
                    'size' => 5,
                    'tmp_name' => vfsStream::path('/tmp/ab'),
                    'error' => UPLOAD_ERR_OK
                ]),
                'extra' => [
                    new UploadedFile([
                        'name' => 'bar.txt',
                        'type' => 'text/plain',
                        'size' => 7,
                        'tmp_name' => vfsStream::path('/tmp/cd'),
                        'error' => UPLOAD_ERR_OK
                    ]),
                    new UploadedFile([
                        'name' => 'zoo.html',
                        'type' => 'text/html',
                        'size' => 9,
                        'tmp_name' => vfsStream::path('/tmp/ef'),
                        'error' => UPLOAD_ERR_OK
                    ]),
                    new UploadedFile([
                        'error' => UPLOAD_ERR_INI_SIZE
                    ])
                ]
            ]);
    }
    
    /**
     * Assert that the global environment has been configured
     */
    protected function assertGlobalEnvironment()
    {
        $this->assertEquals([
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'PATH_INFO' => '/foo',
        ], $_SERVER);
        
        $this->assertEquals(['full' => 1, 'mark' => 'pop'], $_GET);
        $this->assertEquals(['foo' => 'bar'], $_COOKIE);
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $_POST);
        
        $this->assertEquals([
            'file' => ['name' => 'foo.txt', 'type' => 'text/plain', 'size' => 5,
                'tmp_name' => vfsStream::path('/tmp/ab'), 'error' => UPLOAD_ERR_OK],
            'extra' => [
                'name' => ['bar.txt', 'zoo.html', null],
                'type' => ['text/plain', 'text/html', null],
                'size' => [7, 9, null],
                'tmp_name' => [vfsStream::path('/tmp/cd'), vfsStream::path('/tmp/ef'), null],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_INI_SIZE]
            ]
        ], $_FILES);
    }
    
    /**
     * Assert that the environment has been cleaned
     */
    protected function assertCleanedEnvironment()
    {
        $this->assertEquals([], headers_list());
        $this->expectOutputString('');
        
        $this->assertEquals($_GET, $this->globals['get']);
        $this->assertEquals($_POST, $this->globals['post']);
        $this->assertEquals($_COOKIE, $this->globals['cookie']);
        $this->assertEquals($_SERVER, $this->globals['server']);
    }
    
    /**
     * @test
     */
    public function test()
    {
        // Create server request with (actual) global enviroment.
        $request = (new ServerRequest())->withGlobalEnvironment(true);
        
        // Create response with (actual) global enviroment.
        $response = (new Response())->withGlobalEnvironment(true);
        
        // Modifying request, modifies the superglobals.
        $this->initRequest($request);

        // Check environment
        $this->assertGlobalEnvironment();
        
        // The code uses `header` and `echo` to output.
        http_response_code(201);
        header('Location: http://example.com/foo/1');
        header('Content-Type: text/plain');
        echo "Hello world";

        // Disconnect the global environment, copy the data and headers
        $finalResponse = $response->withoutGlobalEnvironment();

        // Remove all headers and output
        $revivedRequest = $request->revive();
        $revivedResponse = $response->revive()->withBody(clone $response->getBody());

        // Revived request and response can be used for next request
        $this->assertFalse($revivedRequest->isStale());
        $this->assertFalse($revivedResponse->isStale());
        
        // Check that the headers and output buffer is cleaned
        $this->assertCleanedEnvironment();
        
        // Check the final response
        $this->assertEquals(201, $finalResponse->getStatusCode());
        $this->assertEquals('http://example.com/foo/1', $finalResponse->getHeaderLine('Location'));
        $this->assertStringStartsWith('text/plain', $finalResponse->getHeaderLine('Content-Type'));
        $this->assertEquals("Hello world", (string)$finalResponse->getBody());
    }
}
