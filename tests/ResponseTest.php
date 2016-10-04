<?php
namespace Jasny\HttpMessage;

use PHPUnit_Framework_TestCase;
use Jasny\HttpMessage\Tests\AssertLastError;
use Jasny\HttpMessage\Response;

/**
 * @covers Jasny\HttpMessage\Response
 */
class ResponseTest extends PHPUnit_Framework_TestCase
{
    use AssertLastError;

    /**
     *
     * @var ServerRequest
     */
    protected $response;
    
    /**
     * @var example http_response
     */
    protected $http_response = ""; 

    public function setUp()
    {
        $this->response = new Response(file_get_contents('/'));
    }

    public function testResponceClass()
    {
        $this->assertInstanceof(Response::class, $this->response);
    }
    
    public function testResponseProtocol(){
        $this->accertTrue(in_array(array('1.0', '1.1', '2'), $this->response->getStatusCode()));
        $this->accertFalse($this->response->getStatusCode() === 1.1);
        $this->accertFalse($this->response->getStatusCode() === 1.0);
        $this->accertFalse($this->response->getStatusCode() === 2);
    }

    public function testInitialResponse()
    {
        $this->accertEquals(200, $this->response->getStatusCode());
        $this->accertEquals('OK', $this->response->getReasonPhrase());
    }
    
    public function testChangeResponseCode()
    {
        $this->response->withStatus(404);
        $this->accertEquals(404, $this->response->getStatusCode());
        $this->accertEquals('Not Found', $this->response->getReasonPhrase());
        $this->accertEquals('Not Found', $this->response->getReasonPhrase());
    }
    

    public function testChangeResponseMessage()
    {
        $this->response->withStatus(404, 'Somthing going wrong');
        $this->accertEquals(404, $this->response->getStatusCode());
        $this->accertEquals('Somthing going wrong', $this->response->getReasonPhrase());
        
        $this->response->withStatus(404, 'Somthing going wrong');
    }
}
