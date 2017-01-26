<?php

namespace Jasny\HttpMessage\ServerRequest;

use Psr\Http\Message\StreamInterface;

/**
 * ServerRequest parsed body methods
 */
trait ParsedBody
{
    /**
     * Parsed body
     * @var null|array|object|mixed
     */
    protected $parsedBody;
    
    /**
     * The condition under which the body was parsed
     * @var array|false|null
     */
    protected $parseCondition;

    /**
     * Linked to $_POST
     * @var array|null
     */
    protected $postData;
    
    
    /**
     * Disconnect the global enviroment, turning stale
     * 
     * @return self  A non-stale request
     */
    abstract protected function copy();

    /**
     * Get the server paramaters (typically $_SERVER)
     * 
     * @return array
     */
    abstract public function getServerParams();
    
    /**
     * Get a header as string
     * 
     * @param string $name
     * @return string
     */
    abstract public function getHeaderLine($name);
    
    /**
     * Get the body
     * 
     * @return StreamInterface
     */
    abstract public function getBody();


    /**
     * Get the MIME from the Content-Type header
     * 
     * @return string
     */
    protected function getContentType()
    {
        $header = $this->getHeaderLine('Content-Type');
        return trim(strstr($header, ';', true) ?: $header);
    }

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
        return
            !isset($this->parseCondition) ||
            (isset($this->parseCondition['content_type']) && $this->parseCondition['content_type'] !==
                $this->getContentType()) ||
            (isset($this->parseCondition['size']) && $this->parseCondition['size'] !== $this->getBody()->getSize());
    }

    /**
     * Set as parsed body, but only if Content-Type is of form upload
     * 
     * @param array $data  Should be $_POST
     */
    protected function setPostData(array &$data)
    {
        $this->postData =& $data;
    }
    
    /**
     * Check if we should use post data rather than parsing the body
     * 
     * @return boolean
     */
    protected function shouldUsePostData()
    {
        if (!isset($this->postData)) {
            return false;
        }
        
        $contentType = $this->getContentType();
        
        return
            in_array($contentType, ['application/x-www-form-urlencoded', 'multipart/form-data']) ||
            empty($contentType) && !array_key_exists('SERVER_PROTOCOL', $this->getServerParams());
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
        
        switch ($this->getContentType()) {
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
            case 'multipart/form-data':
                throw new \RuntimeException("Parsing multipart/form-data isn't supported");
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
            // @codeCoverageIgnore
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
        if ($this->shouldUsePostData()) {
            return $this->postData;
        }

        if ($this->parseBodyIsRequired()) {
            $this->parsedBody = $this->parseBody();

            $this->parseCondition = [
                'content_type' => $this->getContentType(),
                'size' => $this->getBody()->getSize()
            ];
        }
        
        return $this->parsedBody;
    }
    
    
    /**
     * Return an instance with the specified body parameters.
     * Setting the parsed body to `null` means that the body will be (re-)parsed on `getParsedBody()`.
     *
     * @param null|array|object|mixed $data The deserialized body data.
     * @return static
     */
    public function withParsedBody($data)
    {
        $request = $this->copy();

        $request->parseCondition = ($data === null ? null : false);
        
        if ($this->shouldUsePostData() && $data !== null) {
            $request->postData = $data;
            $request->parsedBody = null;
        } else {
            $request->parsedBody = $data;
        }
        
        return $request;
    }
}
