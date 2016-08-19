<?php

namespace Jasny\HttpMessage;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

use Jasny\HttpMessage\Stream;
use Jasny\HttpMessage\DerivedAttribute;

/**
 * Representation of an incoming, server-side HTTP request.
 */
class ServerRequest implements ServerRequestInterface
{
    /**
     * Server parameters, typically $_SERVER
     * @var array
     */
    protected $serverParams;
    
    /**
     * Cookies, typically $_COOKIE
     * @var array
     */
    protected $cookies;
    
    /**
     * Query parameters, typically $_GET
     * @var array
     */
    protected $queryParams;
    
    /**
     * Post data, typically $_POST
     * @var array
     */
    protected $postData;
    
    /**
     * Uploaded files, typically $_FILES
     * @var array
     */
    protected $uploadedFiles;
    
    /**
     * Request body, typically php://input
     * @var StreamInterface 
     */
    protected $body;
    
    /**
     * Derived attributes
     * @var array
     */
    protected $attributes;
    
    
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->createDerivedAttributes();
    }
    
    /**
     * Create derived attribute objects
     */
    protected function createDerivedAttributes()
    {
        $this->attributes = [
            'client_ip' => new DerivedAttribute\ClientIp(),
            'content_type' => new DerivedAttribute\ContentType()
        ];
    }
    
    
    /**
     * Use super globals $_SERVER, $_COOKIE, $_GET, $_POST and $_FILES and the php://input stream.
     * 
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @return self
     */
    public function withSuperGlobals()
    {
        $request = clone $this;
        
        $request->serverParams =& $_SERVER;
        $request->cookies =& $_COOKIE;
        $request->queryParams =& $_GET;
        $request->postData =& $_POST;
        $request->uploadedFiles =& $_FILES;
        
        $request->body = new Stream('php://input');
        
        return $request;
    }

    
    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * @param string $version HTTP protocol version
     * @return static
     * @throws \InvalidArgumentException for invalid versions
     */
    public function withProtocolVersion($version)
    {
        
    }
    
    
    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ': ' . implode(', ', $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return string[][] Returns an associative array of the message's headers.
     *     Each key MUST be a header name, and each value MUST be an array of
     *     strings for that header.
     */
    public function getHeaders()
    {
        
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name)
    {
        
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name)
    {
        
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name)
    {
        
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names.
     * @throws \InvalidArgumentException for invalid header values.
     */
    public function withAddedHeader($name, $value)
    {
        
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name)
    {
        
    }
    

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        
    }

    /**
     * Return an instance with the specified message body.
     *
     * @param StreamInterface $body Body.
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        
    }


    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method returns the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * @see http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget)
    {
        
    }
    
    
    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * @param string $method Case-sensitive method.
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        
    }

    
    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        
    }

    /**
     * Returns an instance with the provided URI.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        
    }

    
    /**
     * Retrieve server parameters.
     * Typically the $_SERVER superglobal.
     *
     * @return array
     */
    public function getServerParams()
    {
        
    }

    /**
     * Return an instance with the specified server params.
     * 
     * Note: this method is not part of the PSR-7 specs.
     * 
     * @param array $params Array of key/value pairs server parameters.
     * @return static
     */
    public function withServerParams(array $params)
    {
        
    }

    /**
     * Retrieves cookies sent by the client to the server.
     * Typically the $_COOKIE superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        
    }

    /**
     * Return an instance with the specified cookies.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies)
    {
        
    }

    /**
     * Retrieves the deserialized query string arguments, if any.
     * Typically the $_GET superglobal.
     *
     * @return array
     */
    public function getQueryParams()
    {
        
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * @param array $query Array of query string arguments
     * @return static
     */
    public function withQueryParams(array $query)
    {
        
    }

    /**
     * Retrieve normalized file upload data.
     * This is typically derived from the superglobal $_FILES.
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles()
    {
        
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        
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
     * @return null|array|object|mixed The deserialized body parameters, if any.
     */
    public function getParsedBody()
    {
        
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * @param null|array|object|mixed $data The deserialized body data.
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is provided.
     */
    public function withParsedBody($data)
    {
        
    }
    
    
    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc.
     * 
     * Attribute names are automatically turned into snake_case.
     *
     * @return mixed[] Attributes derived from the request.
     */
    public function getAttributes()
    {
        $attributes = [];
        
        foreach ($this->attributes as $name => $attr) {
            $value = $attr instanceof \Closure || $attr instanceof DerivedAttribute ? $attr($this) : $attr;
            $attributes[$name] = $value;
        }
        
        return $attributes;
    }
    
    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     * 
     * The attribute name is automatically turned into snake_case.
     *
     * @see getAttributes()
     * @param string $name     The attribute name.
     * @param mixed  $default  Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        $key = \Jasny\snakecase($name);
        
        $attr = isset($this->attributes[$key]) ? $this->attributes[$key] : null;
        $value = $attr instanceof \Closure || $attr instanceof DerivedAttribute ? $attr($this) : $attr;
        
        return isset($value) ? $value : $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * The attribute name is automatically turned into snake_case.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value)
    {
        $request = clone $this;
        
        $key = \Jasny\snakecase($name);
        $request->attributes[$key] = $value;
        
        return $request;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute($name)
    {
        $request = clone $this;
        
        $key = \Jasny\snakecase($name);
        unset($request->attributes[$key]);
        
        return $request;
    }
}
