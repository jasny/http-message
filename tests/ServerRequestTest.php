<?php

namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use Jasny\TestHelper;

use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\Headers as HeaderObject;

/**
 * @covers Jasny\HttpMessage\ServerRequest
 */
class ServerRequestTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;
    
    /**
     * @var ServerRequest
     */
    protected $baseRequest;
    
    /**
     * @var HeadersInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $headers;

    public function setUp()
    {
        $refl = new \ReflectionProperty(ServerRequest::class, 'headers');
        $refl->setAccessible(true);
        
        $this->baseRequest = new ServerRequest();
        $this->headers = $this->createMock(HeaderObject::class);
        $refl->setValue($this->baseRequest, $this->headers);
    }
    
    protected function setContentType($contentType)
    {
        $this->headers->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->will($this->returnValue($contentType));
    }

    public function testWithGlobalEnvironment()
    {
        $request = $this->baseRequest->withGlobalEnvironment(false);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertNull($request->isStale());
    }

    public function testWithGlobalEnvironmentBind()
    {
        $request = $this->baseRequest->withGlobalEnvironment(true);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        
        $this->assertEquals('php://input', $request->getBody()
            ->getMetadata('uri'));
        
        $this->assertFalse($request->isStale());
    }

    public function testWithGlobalEnvironmentReset()
    {
        $request = $this->baseRequest->withMethod('POST')->withGlobalEnvironment();
        
        $this->assertEquals('', $request->getMethod());
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Unable to use a stale server request. Did you mean to rivive it?
     */
    public function testWithGlobalEnvironmentStale()
    {
        $this->setPrivateProperty($this->baseRequest, 'isStale', true);
        
        $this->baseRequest->withGlobalEnvironment(true);
    }
    
    public function testWithGlobalEnvironmentGlobal()
    {
        $this->setPrivateProperty($this->baseRequest, 'isStale', false);
        
        $request = $this->baseRequest->withGlobalEnvironment(true);
        
        $this->assertInstanceof(ServerRequest::class, $request);
        $this->assertNotSame($this->baseRequest, $request);
        $this->assertFalse($request->isStale());
        
        $this->assertTrue($this->baseRequest->isStale());
    }

    /**
     * ServerRequest::setPostData is protected, because it should only be used for $_POST
     */
    public function testSetPostDataAndTurnStale()
    {
        $post = ['foo' => 'bar'];
        
        $this->headers->expects($this->once())
            ->method('withHeader')
            ->will($this->returnSelf());
        
        $this->headers->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->will($this->returnValue('application/x-www-form-urlencoded'));
        
        $request = $this->baseRequest->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        $refl = new \ReflectionMethod(ServerRequest::class, 'setPostData');
        $refl->setAccessible(true);
        $refl->invokeArgs($request, [&$post]);
        
        $this->assertSame($post, $request->getParsedBody());
        
        // Test if data is set by reference
        $post['qux'] = 'zoo';
        $this->assertSame($post, $request->getParsedBody());
        
        // Test becoming stale
        $isStale = new \ReflectionProperty(ServerRequest::class, 'isStale');
        $isStale->setAccessible(true);
        $isStale->setValue($request, false);
        
        $newRequest = $request->withParsedBody(['color' => 'blue']);
        $this->assertTrue($request->isStale());
        $this->assertFalse($newRequest->isStale());
        
        $this->assertSame(['color' => 'blue'], $post);
        $this->assertSame(['color' => 'blue'], $newRequest->getParsedBody());
        $this->assertSame(['foo' => 'bar', 'qux' => 'zoo'], $request->getParsedBody());
        
        $post = ['color' => 'red'];
        $this->assertSame($post, $newRequest->getParsedBody());
    }

    public function testWithoutGlobalEnvironmentDefault()
    {
        $this->assertSame($this->baseRequest, $this->baseRequest->withoutGlobalEnvironment());
    }

    public function testWithoutGlobalEnvironment()
    {
        $data = ['foo' => 'bar'];
        
        $this->headers->expects($this->once())
            ->method('withHeader')
            ->will($this->returnSelf());
        
        $this->setContentType('application/x-www-form-urlencoded');
        
        $request = $this->baseRequest->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        $refl = new \ReflectionMethod(ServerRequest::class, 'setPostData');
        $refl->setAccessible(true);
        $refl->invokeArgs($request, [&$data]);
        
        // Test becoming stale
        $isStale = new \ReflectionProperty(ServerRequest::class, 'isStale');
        $isStale->setAccessible(true);
        $isStale->setValue($request, false);
        
        $detached = $request->withoutGlobalEnvironment();
        $this->assertFalse($request->isStale());
        $this->assertNull($detached->isStale());
        
        $data['qux'] = 'zoo';
        $this->assertEquals($data, $request->getParsedBody());
        $this->assertEquals(['foo' => 'bar'], $detached->getParsedBody());
    }

    public function testIsStale()
    {
        $this->assertNull($this->baseRequest->isStale());
        
        $this->setPrivateProperty($this->baseRequest, 'isStale', false);
        $this->assertFalse($this->baseRequest->isStale());
        
        $this->setPrivateProperty($this->baseRequest, 'isStale', true);
        $this->assertTrue($this->baseRequest->isStale());
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Unable to modify a stale server request object
     */
    public function testModifyWhenStale()
    {
        $this->setPrivateProperty($this->baseRequest, 'isStale', true);
        
        $this->baseRequest->withProtocolVersion('1.0');
    }
    
    public function testRevive()
    {
        $body = $this->createMock(Stream::class);
        $uploadedFile = $this->createMock(UploadedFile::class);
        
        $methods = [
            'ServerParams' => ['HTTP_FOO' => 'bar', 'CLIENT_IP' => '127.0.0.1'],
            'CookieParams' => ['uid' => 123],
            'QueryParams' => ['color' => 'red'],
            'ParsedBody' => ['hello' => 'world'],
            'Body' => $body,
            'UploadedFiles' => ['file' => $uploadedFile]
        ];
        
        $this->baseRequest = $this->createPartialMock(
            ServerRequest::class,
            array_merge(
                array_map(function ($method) { return "get{$method}"; }, array_keys($methods)),
                ['buildGlobalEnvironment']
            )
        );

        foreach ($methods as $method => $value) {
            $this->baseRequest->method("get{$method}")->willReturn($value);
        }
        
        $revivedRequest = $this->createPartialMock(
            ServerRequest::class,
            array_map(function ($method) { return "with{$method}"; }, array_keys($methods))
        );
        
        foreach ($methods as $method => $value) {
            $revivedRequest->method("with{$method}")->with($value)->willReturnSelf();
        }
        
        $this->setPrivateProperty($this->baseRequest, 'isStale', true);
        $this->baseRequest->expects($this->once())->method('buildGlobalEnvironment')
            ->willReturn($revivedRequest);
        
        $ret = $this->baseRequest->revive();
        
        $this->assertSame($revivedRequest, $ret);
    }
    
    public function testReviveNoStale()
    {
        $ret = $this->baseRequest->revive();
        
        $this->assertSame($this->baseRequest, $ret);
    }
}
