<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use Jasny\HttpMessage\Tests\AssertLastError;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\Stream;
use Jasny\HttpMessage\Uri;
use Jasny\HttpMessage\UploadedFile;
use Jasny\HttpMessage\DerivedAttribute;

/**
 * @covers Jasny\HttpMessage\ServerRequest
 * @covers Jasny\HttpMessage\ServerRequest\ProtocolVersion
 * @covers Jasny\HttpMessage\ServerRequest\Headers
 * @covers Jasny\HttpMessage\ServerRequest\Body
 * @covers Jasny\HttpMessage\ServerRequest\RequestTarget
 * @covers Jasny\HttpMessage\ServerRequest\Method
 * @covers Jasny\HttpMessage\ServerRequest\Uri
 * @covers Jasny\HttpMessage\ServerRequest\ServerParams
 * @covers Jasny\HttpMessage\ServerRequest\Cookies
 * @covers Jasny\HttpMessage\ServerRequest\QueryParams
 * @covers Jasny\HttpMessage\ServerRequest\UploadedFiles
 * @covers Jasny\HttpMessage\ServerRequest\ParsedBody
 * @covers Jasny\HttpMessage\ServerRequest\Attributes
 */
class ServerRequestTest extends PHPUnit_Framework_TestCase
{
    use AssertLastError;
    
    /**
     * @var ServerRequest
     */
    protected $baseRequest;
    
    public function setUp()
    {
        $this->baseRequest = new ServerRequest();
    }
    
    /**
     * Get mock with original methods and constructor disabled
     * 
     * @param string $classname
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSimpleMock($classname)
    {
        return $this->getMockBuilder($classname)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->disableOriginalClone()
            ->getMock();
    }
    
    
    public function testWithSuperGlobals()
    {
        $request = $this->baseRequest->withSuperGlobals();
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('php://input', $request->getBody()->getMetadata('uri'));
    }
    
    public function testWithSuperGlobalsReset()
    {
        $request = $this->baseRequest
            ->withMethod('POST')
            ->withSuperGlobals();
        
        $this->assertEquals('', $request->getMethod());
    }
    
    
    public function testGetServerParamsDefault()
    {
        $this->assertSame([], $this->baseRequest->getServerParams());
    }
    
    public function testWithServerParams()
    {
        $params = [
            'SERVER_SOFTWARE' => 'Foo 1.0',
            'COLOR' => 'red',
            'SCRIPT_FILENAME' => 'qux.php'
        ];
        
        $request = $this->baseRequest->withServerParams($params);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals($params, $request->getServerParams());
    }

    /**
     * @depends testWithServerParams
     */
    public function testWithServerParamsReset()
    {
        $request = $this->baseRequest
            ->withMethod('POST')
            ->withServerParams([]);
        
        $this->assertEquals('', $request->getMethod());
    }
    
    
    public function testDefaultProtocolVersion()
    {
        $this->assertEquals('1.0', $this->baseRequest->getProtocolVersion());
    }
    
    public function testDetermineProtocolVersion()
    {
        $request = $this->baseRequest->withServerParams(['SERVER_PROTOCOL' => 'HTTP/1.1']);
        
        $this->assertEquals('1.1', $request->getProtocolVersion());
    }
    
    public function testWithProtocolVersion()
    {
        $request = $this->baseRequest->withProtocolVersion('1.1');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('1.1', $request->getProtocolVersion());
    }
    
    public function testWithProtocolVersionFloat()
    {
        $request = $this->baseRequest->withProtocolVersion(2.0);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('2.0', $request->getProtocolVersion());
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid HTTP protocol version '0.2'
     */
    public function testWithInvalidProtocolVersion()
    {
        $this->baseRequest->withProtocolVersion('0.2');
    }
    
    public function testGetHeadersDefault()
    {
        $headers = $this->baseRequest->getHeaders();
        $this->assertSame([], $headers);
    }
    
    public function testDetermineHeaders()
    {
        $request = $this->baseRequest->withServerParams([
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'CONTENT_TYPE' => 'text/plain',
            'CONTENT_LENGTH' => '20',
            'HTTP_HOST' => 'example.com',
            'HTTP_X_FOO' => 'bar',
            'HTTP_CONTENT_TYPE' => 'text/plain',
            'HTTPS' => 1
        ]);
        
        $this->assertEquals([
            'Content-Type' => ['text/plain'],
            'Content-Length' => ['20'],
            'Host' => ['example.com'],
            'X-Foo' => ['bar']
        ], $request->getHeaders());
    }
    
    public function testWithHeader()
    {
        $request = $this->baseRequest->withHeader('foo-zoo', 'red & blue');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['Foo-Zoo' => ['red & blue']], $request->getHeaders());
        
        return $request;
    }
    
    public function testWithHeaderArray()
    {
        $request = $this->baseRequest->withHeader('foo-zoo', ['red', 'blue']);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['Foo-Zoo' => ['red', 'blue']], $request->getHeaders());
        
        return $request;
    }
    
    /**
     * @depends testWithHeader
     */
    public function testWithHeaderAddAnother(ServerRequest $origRequest)
    {
        $request = $origRequest->withHeader('QUX', 'white');
        $this->assertEquals([
            'Foo-Zoo' => ['red & blue'],
            'Qux' => ['white']
        ], $request->getHeaders());
        
        return $request;
    }
    
    /**
     * @depends testWithHeader
     */
    public function testWithHeaderOverwrite(ServerRequest $origRequest)
    {
        $request = $origRequest->withHeader('foo-zoo', 'silver & gold');
        $this->assertEquals(['Foo-Zoo' => ['silver & gold']], $request->getHeaders());
    }
    
    /**
     * @depends testWithHeader
     */
    public function testWithAddedHeader(ServerRequest $origRequest)
    {
        $request = $origRequest->withAddedHeader('foo-zoo', 'silver & gold');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['Foo-Zoo' => ['red & blue', 'silver & gold']], $request->getHeaders());
    }
    
    public function testWithAddedHeaderNew()
    {
        $request = $this->baseRequest->withAddedHeader('QUX', 'white');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['Qux' => ['white']], $request->getHeaders());
    }
    
    /**
     * @depends testWithHeaderAddAnother
     */
    public function testWithoutHeader(ServerRequest $origRequest)
    {
        $request = $origRequest->withoutHeader('foo-zoo');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['Qux' => ['white']], $request->getHeaders());
    }
    
    public function testWithoutHeaderNotExists()
    {
        $request = $this->baseRequest->withoutHeader('not-exists');
        $this->assertSame($this->baseRequest, $request);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Header name should be a string
     */
    public function testWithHeaderArrayAsName()
    {
        $this->baseRequest->withHeader(['foo' => 'bar'], 'zoo');
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid header name 'foo bar'
     */
    public function testWithHeaderInvalidName()
    {
        $this->baseRequest->withHeader('foo bar', 'zoo');
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Header value should be a string or an array of strings
     */
    public function testWithHeaderArrayAsValue()
    {
        $this->baseRequest->withHeader('foo', ['bar', ['zoo', 'woo']]);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Header name should be a string
     */
    public function testWithAddedHeaderArrayAsName()
    {
        $this->baseRequest->withAddedHeader(['foo' => 'bar'], 'zoo');
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid header name 'foo bar'
     */
    public function testWithAddedHeaderInvalidName()
    {
        $this->baseRequest->withAddedHeader('foo bar', 'zoo');
    }
    
    public function testWithoutHeaderArrayAsName()
    {
        $request = $this->baseRequest->withoutHeader(['foo', 'bar']);
        $this->assertSame($this->baseRequest, $request);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Header value should be a string or an array of strings
     */
    public function testWithAddedHeaderArrayAsValue()
    {
        $this->baseRequest->withAddedHeader('foo', ['bar', ['zoo', 'woo']]);
    }
    
    public function testHasHeader()
    {
        $request = $this->baseRequest->withHeader('Foo', 'red');
        $this->assertInstanceof(ServerRequest::class, $request);
        
        $this->assertTrue($request->hasHeader('FoO'));
        $this->assertFalse($request->hasHeader('NotExists'));
    }
    
    public function testGetHeader()
    {
        $request = $this->baseRequest->withHeader('Foo', ['red', 'blue']);
        $this->assertInstanceof(ServerRequest::class, $request);
        
        $this->assertEquals(['red', 'blue'], $request->getHeader('FoO'));
    }
    
    public function testGetHeaderNotExists()
    {
        $this->assertEquals([], $this->baseRequest->getHeader('NotExists'));
    }
    
    public function testGetHeaderLine()
    {
        $request = $this->baseRequest->withHeader('Foo', ['red', 'blue']);
        $this->assertInstanceof(ServerRequest::class, $request);
        
        $this->assertEquals('red,blue', $request->getHeaderLine('FoO'));
    }
    
    public function testGetHeaderLineNotExists()
    {
        $this->assertEquals('', $this->baseRequest->getHeaderLine('NotExists'));
    }
    
    
    public function testGetBodyDefault()
    {
        $body = $this->baseRequest->getBody();
        
        $this->assertInstanceOf(Stream::class, $body);
        $this->assertEquals('data://text/plain,', $body->getMetadata('uri'));
    }
    
    public function testWithBody()
    {
        $stream = $this->getSimpleMock(Stream::class);
        $request = $this->baseRequest->withBody($stream);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame($stream, $request->getBody());
    }
    
    
    public function testGetRequestTargetDefault()
    {
        $this->assertEquals('/', $this->baseRequest->getRequestTarget());
    }
    
    public function testDetermineRequestTarget()
    {
        $request = $this->baseRequest->withServerParams(['REQUEST_URI' => '/foo?bar=1']);
        $this->assertEquals('/foo?bar=1', $request->getRequestTarget());
    }
    
    public function testDetermineRequestTargetOrigin()
    {
        $request = $this->baseRequest->withServerParams(['REQUEST_METHOD' => 'OPTIONS']);
        $this->assertEquals('*', $request->getRequestTarget());
    }
    
    public function testWithRequestTarget()
    {
        $request = $this->baseRequest->withRequestTarget('/foo?bar=99');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('/foo?bar=99', $request->getRequestTarget());
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Request target should be a string, not a stdClass object
     */
    public function testWithRequestTargetWithInvalidArgument()
    {
        $this->baseRequest->withRequestTarget((object)['foo' => 1, 'bar' => 2, 'zoo' => 3]);
    }
    
    
    public function testGetMethodDefault()
    {
        $this->assertSame('', $this->baseRequest->getMethod());
    }
    
    public function testDetermineMethod()
    {
        $request = $this->baseRequest->withServerParams(['REQUEST_METHOD' => 'post']);
        $this->assertEquals('POST', $request->getMethod());
    }
    
    public function testWithMethod()
    {
        $request = $this->baseRequest->withMethod('GeT');
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('GET', $request->getMethod());
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid method 'foo bar': Method may only contain letters and dashes
     */
    public function testWithMethodInvalid()
    {
        $this->baseRequest->withMethod("foo bar");
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Method should be a string, not a stdClass object
     */
    public function testWithMethodInvalidArgument()
    {
        $this->baseRequest->withMethod((object)['foo' => 1, 'bar' => 2]);
    }
    
    
    public function testGetUriDefault()
    {
        $uri = $this->baseRequest->getUri();
        
        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertEquals(new Uri(), $uri);
    }
    
    public function testDetermineUri()
    {
        $request = $this->baseRequest->withServerParams([
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PWD' => 'secure',
            'HTTP_HOST' => 'www.example.com',
            'SERVER_PORT' => 80,
            'PATH_INFO' => '/page/bar',
            'QUERY_STRING' => 'color=red'
        ]);
        
        $this->assertEquals(new Uri([
            'scheme' => 'http',
            'user' => 'foo',
            'password' => 'secure',
            'host' => 'www.example.com',
            'port' => 80,
            'path' => '/page/bar',
            'query' => 'color=red'
        ]), $request->getUri());
    }
    
    public function testDetermineUriHttps()
    {
        $protocol = ['SERVER_PROTOCOL' => 'HTTP/1.1'];
        $request = $this->baseRequest;
        
        $this->assertEquals('http', $request->withServerParams($protocol)->getUri()->getScheme());
        $this->assertEquals('http', $request->withServerParams($protocol + ['HTTPS' => ''])->getUri()->getScheme());
        $this->assertEquals('http', $request->withServerParams($protocol + ['HTTPS' => 'off'])->getUri()->getScheme());
        
        $this->assertEquals('https', $request->withServerParams($protocol + ['HTTPS' => '1'])->getUri()->getScheme());
        $this->assertEquals('https', $request->withServerParams($protocol + ['HTTPS' => 'on'])->getUri()->getScheme());
    }
    
    public function testWithUri()
    {
        $uri = $this->getSimpleMock(Uri::class);
        $uri->expects($this->once())->method('getHost')->willReturn('www.example.com');
        
        $request = $this->baseRequest->withUri($uri);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame($uri, $request->getUri());
        $this->assertEquals(['www.example.com'], $request->getHeader('Host'));
    }
    
    public function testWithUriPreserveHost()
    {
        $uri = $this->getSimpleMock(Uri::class);
        $uri->expects($this->never())->method('getHost');
        
        $request = $this->baseRequest->withUri($uri, true);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame($uri, $request->getUri());
        $this->assertEquals([], $request->getHeader('Host'));
    }
    
    
    public function testGetCookieParamsDefault()
    {
        $this->assertSame([], $this->baseRequest->getCookieParams());
    }
    
    public function testWithCookieParams()
    {
        $request = $this->baseRequest->withCookieParams(['foo' => 'bar', 'color' => 'red']);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame(['foo' => 'bar', 'color' => 'red'], $request->getCookieParams());
    }
    
    
    public function testGetQueryParamsDefault()
    {
        $this->assertSame([], $this->baseRequest->getQueryParams());
    }
    
    public function testWithQueryParams()
    {
        $request = $this->baseRequest->withQueryParams(['foo' => 'bar', 'color' => 'red']);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame(['foo' => 'bar', 'color' => 'red'], $request->getQueryParams());
    }
    
    
    public function testGetUploadedFilesDefault()
    {
        $this->assertSame([], $this->baseRequest->getUploadedFiles());
    }
    
    /**
     * ServerRequest::setUploadFiles() is protected, because it can only be used for $_FILES
     */
    public function testSetUploadedFiles()
    {
        $refl = new \ReflectionMethod(ServerRequest::class, 'setUploadedFiles');
        $refl->setAccessible(true);
        
        $files = [
            'file' => [
                'name' => 'foo.txt',
                'type' => 'text/plain',
                'size' => 3,
                'tmp_name' => 'data://text/plain,foo',
                'error' => UPLOAD_ERR_OK
            ],
            'failed' => [
                'name' => '',
                'type' => '',
                'size' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE
            ]
        ];
        
        $refl->invoke($this->baseRequest, $files);
        $uploadedFiles = $this->baseRequest->getUploadedFiles();
        
        $this->assertInternalType('array', $uploadedFiles);
        
        $this->assertArrayHasKey('file', $uploadedFiles);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['file']);
        $this->assertEquals(new UploadedFile($files['file'], 'file'), $uploadedFiles['file']);
        
        $this->assertArrayHasKey('failed', $uploadedFiles);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['failed']);
        $this->assertEquals(new UploadedFile($files['failed'], 'failed'), $uploadedFiles['failed']);
    }
    
    /**
     * ServerRequest::setUploadFiles() is protected, because it can only be used for $_FILES
     */
    public function testGroupUploadedFiles()
    {
        $refl = new \ReflectionMethod(ServerRequest::class, 'setUploadedFiles');
        $refl->setAccessible(true);
        
        $files = [
            'file' => [
                'name' => 'foo.txt',
                'type' => 'text/plain',
                'size' => 3,
                'tmp_name' => 'data://text/plain,foo',
                'error' => UPLOAD_ERR_OK
            ],
            'colors' => [
                'name' => [
                    'blue' => 'navy.txt',
                    'red' => 'cherry.html'
                ],
                'type' => [
                    'blue' => 'text/plain',
                    'red' => 'text/html'
                ],
                'size' => [
                    'blue' => 4,
                    'red' => 15
                ],
                'tmp_name' => [
                    'blue' => 'data://text/plain,navy',
                    'red' => 'data://text/html,<h1>cherry</h1>'
                ],
                'error' => [
                    'blue' => UPLOAD_ERR_OK,
                    'red' => UPLOAD_ERR_OK
                ]
            ]
        ];
        
        $blue = [
            'name' => 'navy.txt',
            'type' => 'text/plain',
            'size' => 4,
            'tmp_name' => 'data://text/plain,navy',
            'error' => UPLOAD_ERR_OK,
        ];
        
        $red = [
            'name' => 'cherry.html',
            'type' => 'text/html',
            'size' => 15,
            'tmp_name' => 'data://text/html,<h1>cherry</h1>',
            'error' => UPLOAD_ERR_OK,
        ];
        
        $refl->invoke($this->baseRequest, $files);
        $uploadedFiles = $this->baseRequest->getUploadedFiles();
        
        $this->assertInternalType('array', $uploadedFiles);
        
        $this->assertArrayHasKey('file', $uploadedFiles);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['file']);
        $this->assertEquals(new UploadedFile($files['file'], 'file'), $uploadedFiles['file']);
        
        $this->assertArrayHasKey('colors', $uploadedFiles);
        $this->assertInternalType('array', $uploadedFiles['colors']);

        $this->assertArrayHasKey('blue', $uploadedFiles['colors']);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['colors']['blue']);
        $this->assertEquals(new UploadedFile($blue, 'colors[blue]'), $uploadedFiles['colors']['blue']);

        $this->assertArrayHasKey('red', $uploadedFiles['colors']);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['colors']['red']);
        $this->assertEquals(new UploadedFile($red, 'colors[red]'), $uploadedFiles['colors']['red']);
    }
    
    public function testWithUploadedFiles()
    {
        $uploadedFile = $this->getSimpleMock(UploadedFile::class);
        $request = $this->baseRequest->withUploadedFiles(['file' => $uploadedFile]);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame(['file' => $uploadedFile], $request->getUploadedFiles());
    }
    
    public function testWithUploadedFilesStructure()
    {
        $file = $this->getSimpleMock(UploadedFile::class);
        $blue = clone $file;
        $red = clone $file;
        
        $files = ['file' => $file, 'colors' => compact('blue', 'red')];
        
        $request = $this->baseRequest->withUploadedFiles($files);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertSame($files, $request->getUploadedFiles());
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage 'colors[red]' is not an UploadedFileInterface object, but a string
     */
    public function testWithUploadedFilesInvalidStructure()
    {
        $file = $this->getSimpleMock(UploadedFile::class);
        $blue = clone $file;
        $red = 'hello';
        
        $this->baseRequest->withUploadedFiles(['file' => $file, 'colors' => compact('blue', 'red')]);
    }
    
    
    public function testGetParsedBodyDefault()
    {
        $this->assertNull($this->baseRequest->getParsedBody());
    }
    
    public function testParseUrlEncodedBody()
    {
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->once())->method('__toString')->willReturn('foo=bar&color=red');
        
        $request = $this->baseRequest
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($body);
        
        $this->assertEquals(['foo' => 'bar', 'color' => 'red'], $request->getParsedBody());
        
        return $request;
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Parsing multipart/form-data isn't supported
     */
    public function testParseMultipartBody()
    {
        $request = $this->baseRequest
            ->withHeader('Content-Type', 'multipart/form-data');
        
        $request->getParsedBody();
    }
    
    public function testParseJsonBody()
    {
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->once())->method('__toString')->willReturn('{"foo":"bar","color":"red"}');
        
        $request = $this->baseRequest
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);
        
        $this->assertEquals(['foo' => 'bar', 'color' => 'red'], $request->getParsedBody());
    }
    
    public function testParseInvalidJsonBody()
    {
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->once())->method('__toString')->willReturn('not json');
        
        $request = $this->baseRequest
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);
        
        $this->assertNull(@$request->getParsedBody());
        $this->assertLastError(E_USER_WARNING, 'Failed to parse json body: Syntax error');
    }
    
    public function testParseXmlBody()
    {
        if (!function_exists('simplexml_load_string')) {
            return $this->markTestSkipped('SimpleXML extension not loaded');
        }
        
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->once())->method('__toString')->willReturn('<foo>bar</foo>');
        
        $request = $this->baseRequest
            ->withHeader('Content-Type', 'text/xml')
            ->withBody($body);
        
        $parsedBody = $request->getParsedBody();
        
        $this->assertInstanceOf(\SimpleXMLElement::class, $parsedBody);
        $this->assertXmlStringEqualsXmlString('<foo>bar</foo>', $parsedBody->asXML());
    }
    
    public function testParseInvalidXmlBody()
    {
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->once())->method('__toString')->willReturn('not xml');
        
        $request = $this->baseRequest
            ->withHeader('Content-Type', 'text/xml')
            ->withBody($body);
        
        $this->assertNull(@$request->getParsedBody());
        $this->assertLastError(E_WARNING);
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to parse body: 'Content-Type' header is missing
     */
    public function testParseUnknownBody()
    {
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->once())->method('getSize')->willReturn(4);
        
        $request = $this->baseRequest->withBody($body);
        $request->getParsedBody();
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Parsing application/x-foo isn't supported
     */
    public function testParseUnsupportedBody()
    {
        $request = $this->baseRequest->withHeader('Content-Type', 'application/x-foo');
        $request->getParsedBody();
    }

    /**
     * @depends testParseUrlEncodedBody
     */
    public function testResetParsedBody(ServerRequest $originalRequest)
    {
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->once())->method('__toString')->willReturn('foo=do&color=blue'); // Same size
        
        $request = $originalRequest->withBody($body);
        $this->assertEquals(['foo' => 'do', 'color' => 'blue'], $request->getParsedBody());
    }
    
    /**
     * ServerRequest::setPostData is protected, because it should only be used for $_POST
     */
    public function testSetPostData()
    {
        $data = ['foo' => 'bar'];
        
        $refl = new \ReflectionMethod(ServerRequest::class, 'setPostData');
        $refl->setAccessible(true);
        
        $request = $this->baseRequest->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        $refl->invokeArgs($request, [&$data]);
        
        $this->assertSame($data, $request->getParsedBody());
        
        // Test if data is set by reference
        $data['qux'] = 'zoo';
        $this->assertSame($data, $request->getParsedBody());
    }
    
    /**
     * ServerRequest::setPostData is protected, because it should only be used for $_POST
     */
    public function testSetPostDataVsJsonContent()
    {
        $data = [];
        
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->once())->method('__toString')->willReturn('{"foo": "bar"}');
        
        $refl = new \ReflectionMethod(ServerRequest::class, 'setPostData');
        $refl->setAccessible(true);
        
        $request = $this->baseRequest
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);
        
        $refl->invokeArgs($request, [&$data]); // Should have no effect
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
        
        $refl->invokeArgs($request, [&$data]); // Should still have no effect
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
    }
    
    public function testWithParsedBody()
    {
        $request = $this->baseRequest->withParsedBody(['foo' => 'bar']);
        
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
    }
    
    public function testWithParsedBodyNoReset()
    {
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->never())->method('__toString');
        $body->expects($this->never())->method('getSize');
        
        $request = $this->baseRequest
            ->withBody($body)
            ->withParsedBody(['foo' => 'bar']);
        
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Failed to parse json body: Syntax error
     */
    public function testReparseBodyOnContentType()
    {
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->exactly(2))->method('__toString')->willReturn('foo=bar&color=red');
        
        $request = $this->baseRequest
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($body);
        
        $request->getParsedBody();
        $request->withHeader('Content-Type', 'application/json')->getParsedBody();
    }
    
    public function testReparseBodyOnSize()
    {
        $body = $this->getSimpleMock(Stream::class);
        $body->expects($this->exactly(2))->method('__toString')
            ->willReturnOnConsecutiveCalls('foo=bar', 'foo=bar&color=red');
        $body->expects($this->exactly(4))->method('getSize')->willReturnOnConsecutiveCalls(7, 17, 17, 17);
        
        $request = $this->baseRequest
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($body);
        
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
        
        // Second call with appended content for body
        $this->assertEquals(['foo' => 'bar', 'color' => 'red'], $request->getParsedBody());
        
        // Third call with no reparse
        $this->assertEquals(['foo' => 'bar', 'color' => 'red'], $request->getParsedBody());
    }
    
    
    /**
     * `ServerRequest::createDerivedAttributes` is protected, but we don't want to execute the derived attributes
     */
    public function testCreateDerivedAttributes()
    {
        $refl = new \ReflectionProperty(ServerRequest::class, 'attributes');
        $refl->setAccessible(true);
        
        $attributes = $refl->getValue($this->baseRequest);
        
        $this->assertArrayHasKey('client_ip', $attributes);
        $this->assertInstanceOf(DerivedAttribute\ClientIp::class, $attributes['client_ip']);
        
        $this->assertArrayHasKey('is_xhr', $attributes);
        $this->assertInstanceOf(DerivedAttribute\IsXhr::class, $attributes['is_xhr']);
        
        $this->assertArrayHasKey('local_referer', $attributes);
        $this->assertInstanceOf(DerivedAttribute\LocalReferer::class, $attributes['local_referer']);
    }
    
    public function testWithAttribute()
    {
        $request = $this->baseRequest->withAttribute('foo', ['bar', 'zoo']);
        
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals(['bar', 'zoo'], $request->getAttribute('foo'));
        
        return $request;
    }

    /**
     * @depends testWithAttribute
     */
    public function testWithAttributeOverride(ServerRequest $originalRequest)
    {
        $request = $originalRequest->withAttribute('foo', 'black');
        $this->assertEquals('black', $request->getAttribute('foo'));
    }
    
    public function testWithAttributeAsCallback()
    {
        $request = null;
        
        $request = $this->baseRequest->withAttribute('foo', function($arg) use (&$request) {
            $this->assertSame($request, $arg);
            return ['bar', 'zoo'];
        });
        
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertEquals(['bar', 'zoo'], $request->getAttribute('foo'));
    }
    
    public function testWithAttributeAsObject()
    {
        $attribute = $this->getMock(DerivedAttribute::class);
        $request = $this->baseRequest->withAttribute('foo', $attribute);
        
        $attribute->expects($this->once())->method('__invoke')
            ->with($this->identicalTo($request))
            ->willReturn(['bar', 'zoo']);
        
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertEquals(['bar', 'zoo'], $request->getAttribute('foo'));
    }
    
    /**
     * @depends testWithAttribute
     */
    public function testWithoutAttribute(ServerRequest $originalRequest)
    {
        $request = $originalRequest->withoutAttribute('foo');
        
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertNull($request->getAttribute('foo'));
    }
    
    public function testGetAttributes()
    {
        // Remove all attributes, as we don't want to them
        $refl = new \ReflectionProperty(ServerRequest::class, 'attributes');
        $refl->setAccessible(true);
        $refl->setValue($this->baseRequest, []);
        
        $request = $this->baseRequest
            ->withAttribute('foo', function() {
                return ['bar', 'zoo'];
            })
            ->withAttribute('color', 'red');
            
        $this->assertEquals(['foo' => ['bar', 'zoo'], 'color' => 'red'], $request->getAttributes());
    }
}
