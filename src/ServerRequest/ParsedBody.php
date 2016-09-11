<?php

namespace Jasny\HttpMessage\ServerRequest;

use Psr\Http\Message\StreamInterface;

/**
 * ServerRequest parsed body methods
 */
trait ParsedBody
{
    /**
     * Parsed body (typically $_POST or parsed php://input)
     * @var array|mixed
     */
    protected $parsedBody;
    
    /**
     * The condition under which the body was parsed
     * @var array|null
     */
    protected $parseCondition;

    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function turnStale();
    
    
    /**
     * Get a header as string
     * 
     * @param string $name
     * @return string
     */
    abstract function getHeaderLine($name);
    
    /**
     * Get the body
     * 
     * @return StreamInterface
     */
    abstract function getBody();


    /**
     * Reset the parsed body, excepted if it was explicitly set
     */
    protected function resetParsedBody()
    {
        if ($this->parseCondition !== false) {
            $this->parsedBody = null;
            $this->parseCondition = null;
        }
    }
    
    /**
     * Check if the body needs to be (re-)parsed
     * 
     * @return boolean
     */
    protected function parseBodyIsRequired()
    {
        if (!isset($this->parseCondition)) {
            return true;
        }
        
        if (
            isset($this->parseCondition['content_type']) &&
            $this->parseCondition['content_type'] !== $this->getHeaderLine('Content-Type')
        ) {
            return true;
        }
        
        if (isset($this->parseCondition['size']) && $this->parseCondition['size'] !== $this->getBody()->getSize()) {
            return true;
        }
        
        return false;
    }


    /**
     * Set as parsed body, but only if Content-Type is of form upload
     * 
     * @param array $data  Should be $_POST
     */
    protected function setPostData(array &$data)
    {
        $contentType = $this->getHeaderLine('Content-Type');
        
        if (in_array($contentType, ['application/x-www-form-urlencoded', 'multipart/form-data'])) {
            $this->parsedBody =& $data;
            $this->parseCondition = ['content_type' => $contentType];
        }
    }
    
    /**
     * Parse the body based on the content type.
     * 
     * @return mixed
     * @throws \RuntimeException if parsing isn't supported for the content-type
     */
    protected function parseBody()
    {
        $data = null;
        $contentType = $this->getHeaderLine('Content-Type');
        
        switch ($contentType) {
            case '':
                if ($this->getBody()->getSize() > 0) {
                    throw new \RuntimeException("Unable to parse body: 'Content-Type' header is missing");
                }
                break;
            case 'application/x-www-form-urlencoded':
                $data = $this->parseUrlEncodedBody();
                break;
            case 'application/json':
                $data = $this->parseJsonBody();
                break;
            case 'text/xml':
            case 'application/xml':
                $data = $this->parseXmlBody();
                break;
            default:
                throw new \RuntimeException("Parsing $contentType isn't supported");
        }
        
        return $data;
    }

    /**
     * Parse 'application/x-www-form-urlencoded' body
     * 
     * @return array
     */
    protected function parseUrlEncodedBody()
    {
        $data = null;
        parse_str($this->getBody(), $data);
        return $data;
    }
    
    /**
     * Parse json body
     * 
     * @return array|mixed
     */
    protected function parseJsonBody()
    {
        $data = json_decode($this->getBody(), true);
        
        if (!isset($data) && json_last_error()) {
            trigger_error("Failed to parse json body: " . json_last_error_msg(), E_USER_WARNING);
        }
        
        return $data;
    }
    
    /**
     * Parse XML body
     * 
     * @return \SimpleXMLElement
     */
    protected function parseXmlBody()
    {
        if (!function_exists('simplexml_load_string')) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException("Unable to parse XML body: SimpleXML extension isn't loaded");
            // @codeCoverageIgnoreEnd
        }
        
        return simplexml_load_string($this->getBody()) ?: null;
    }
    
    
    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method will
     * return the contents of $_POST.
     *
     * Otherwise, this method returns the results of deserializing
     * the request body content; as parsing returns structured content.
     * 
     * This function assumes that the body is read only or append only, as it
     * will only reparse the body if the size has changed.
     *
     * @return null|array|object|mixed The deserialized body parameters, if any.
     * @throws \RuntimeException if parsing isn't supported for the content-type
     */
    public function getParsedBody()
    {
        if ($this->parseBodyIsRequired()) {
            $this->parsedBody = $this->parseBody();
            $this->parseCondition = [
                'content_type' => $this->getHeaderLine('Content-Type'),
                'size' => $this->getBody()->getSize()
            ];
        }
        
        return $this->parsedBody;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * @param null|array|object|mixed $data The deserialized body data.
     * @return static
     */
    public function withParsedBody($data)
    {
        $request = $this->turnStale();
        
        $request->parsedBody = $data;
        $request->parseCondition = false;
        
        return $request;
    }
}
