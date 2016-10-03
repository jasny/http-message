# Jasny HTTP Message

This library provides an abstraction around PHPs various superglobals as well as controlling the HTTP response. This
practice helps reduce coupling to the superglobals by consumers, and encourages and promotes the ability to test request
consumers.

### Why does this library exist?

This PSR-7 implementation is an abstraction layer over the normal input/output methods and variables like `echo`,
`header()`, `$_POST`, etc. This maskes is different from other implementations. It directly interacts with the global
environment when you're not testing. This means the behaviour of your application will not change by using this
abstraction.

The library only implements those [PSR-7 interfaces](http://www.php-fig.org/psr/psr-7/) focussed on handling a received
HTTP request. If you want to send HTTP request to other webservices, I recommend using
[Guzzle](http://docs.guzzlephp.org/).


## Installation

    composer install jasny/http-message


## Documentation

The library implements the following PSR-7 interfaces

 * [`ServerRequest`](#ServerRequest) implements `Psr\Http\Message\ServerRequestInterface`
 * [`Response`](#Response) implements `Psr\Http\Message\ResponseInterface`
 * [`Stream`](#InputStream) implements `Psr\Http\Message\StreamInterface`
 * [`Uri`](#Uri) implements `Psr\Http\Message\UriInterface`

it defines one interface

 * [`DerivedAttribute`](#DerivedAttribute)

### ServerRequest

The `ServerRequest` class represents an HTTP request as received by the webserver and processed by PHP.

For the full documentation about the `ServerRequest`, please see
[PSR-7 `RequestInterface`](http://www.php-fig.org/psr/psr-7/#3-2-psr-http-message-requestinterface) and
[PSR-7 `ServerRequestInterface`](http://www.php-fig.org/psr/psr-7/#3-2-1-psr-http-message-serverrequestinterface).

To create a `ServerRequest` object with the `$_SERVER`, `$_COOKIE`, `$_GET`, `$_POST` and `$_FILES` superglobals and
with `php://input` as input stream, use the `withGlobalEnvironment()` method.

```php
$request = (new Jasny\HttpMessage\ServerRequest())->withGlobalEnvironment();
```

The `withGlobalEnvironment()` links the superglobals by reference to the object. You SHOULD NOT modify these variables,
but if you do the changes will be reflected in the `ServerRequest` object. Vise versa, using `withQueryParams()` will
change `$_GET`, `withServerParams` changes `$_SERVER`, etc.

If you do not want this behaviour and want the request to copy the values of the superglobals instead, set the first
argument to `false`.

```php
// $_GET is affected
$requestByRef = (new Jasny\HttpMessage\ServerRequest())->withGlobalEnvironment();
$requestByRef = $request->withQueryParams(['foo' => 1]);
var_dump($_GET); // array(1) { ["foo"]=> int(1) }

// $_GET is not affected
$requestByVal = (new Jasny\HttpMessage\ServerRequest())->withGlobalEnvironment(false);
$requestByVal = $request->withQueryParams(['foo' => 1]);
var_dump($_GET); // array(0) { }
```

### Response

The `Response` class allows you to create the outgoing HTTP response.

For the full documentation about the `Response` class, please see
[PSR-7 `ResponseInterface`](http://www.php-fig.org/psr/psr-7/#3-3-psr-http-message-responseinterface).

By default a `Response` object will stream to `php://memory` and simply hold a list of all set headers.

```
$request = new Jasny\HttpMessage\ServerRequest();
```

To create a `Response` object which uses the [`header()`](http://php.net/manual/en/function.header.php) method and
with `php://output` as output stream, use the `withGlobalEnvironment()` method.

```php
$request = (new Jasny\HttpMessage\ServerRequest())->withGlobalEnvironment();
```

You SHOULD NOT write to `php://output` directly (by using `echo`). Still, the changes will be reflected in the
`Response` object when `withGlobalEnvironment()` is used.

You SHOULD NOT use the `header()` and `header_remove()` functions directly. Still, the changes will be reflected in the 
`Response` object when `withGlobalEnvironment()` is used.


### Uri

The `Uri` class is meant to represent URIs according to [RFC 3986](https://www.ietf.org/rfc/rfc3986.txt). It allows you
to get and change any specific part of an uri.

For the full documentation about the `Uri` class, please see
[PSR-7 `UriInterface`](http://www.php-fig.org/psr/psr-7/#3-5-psr-http-message-uriinterface).

When creating an Uri you can pass the URL as string or pass the URL in parts as associative array. For the URL parts
see the [`parse_url`](http://www.php.net/parse_url) function.

The `Jasny\HttpMessage\Uri` object only supports the `http` and `https` schemes.

```php
$uri = new Jasny\HttpMessage\Uri("http://www.example.com/foo");
```


### Stream

The `Stream` class is a wrapper around [php streams](http://php.net/manual/en/book.stream.php) implementing the
[PSR-7 `StreamInterface`](http://www.php-fig.org/psr/psr-7/#3-4-psr-http-message-streaminterface).

```php
$input = new Jasny\HttpMessage\Stream('php://input', 'r');
$output = new Jasny\HttpMessage\Stream('php://output', 'w');
```

For testing purposes use the `php://memory` stream.

```php
$input = new Jasny\HttpMessage\Stream('php://memory');
$input->write(json_encode(['foo' => 'bar', 'color' => 'red']));

$output = new Jasny\HttpMessage\Stream('php://memory');
```

After running the test case, cast `$output` to a string to assert the output.


### DerivedAttribute

You can set arbitrary attributes for a `ServerRequest` using the `withAttribute()` method. To get an attribute use the
`getAttribute()` method.

An attribute can be set to any static value, or it can be derived from other values of a `ServerRequest` object, like a
header or query parameter. The easiest way to create a derived attribute is to use a
[`Closure`](http://www.php.net/closure).

```php
use Jasny\HttpMessage\ServerRequest;

$request = (new ServerRequest())->withAttribute('accept_json', function(ServerRequest $request) {
    $accept = $request->getHeaderLine('Accept');
    return strpos($accept, 'application/json') !== false || strpos($accept, '*/*') !== false;
});
```

You can create more sophisticated derived attributes by creating a class that implements the `DerivedAttribute`
interface. When implementing that interface, implement `__invoke(ServerRequest $request)`. 

```php
use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\DerivedAttribute;

class DetectBot implements DerivedAttribute
{
    public static $identifiers = [
        'google' => 'googlebot',
        'yahoo' => 'yahoobot',
        'magpie' => 'magpie-crawler'
    ];

    protected $detect = [];
    
    public function __construct(array $detect)
    {
        $this->detect = $detect;
    }

    public function __invoke(ServerRequest $request)
    {
        $useragent = $request->getHeaderLine('User-Agent');
        $detected = false;

        foreach ($this->detect as $bot) {
            $identifier = static::$identifiers[$bot];
            $detected = $detected || stripos($useragent, $bot) !== false;
        }

        return $detected;
    }
}

$request = (new ServerRequest())
    ->withAttribute('is_friendly_bot', new DetectBot(['google', 'yahoo']))
    ->withAttribute('is_annoying_bot', new DetectBot(['magpie']));
```

_Remember that a `ServerRequest` method is immutability, so `withAttribute()` will create a new object._

This library comes with a number of derived attributes, which may be used.

#### ClientIp

Get the client IP. By default only `$_SERVER['REMOTE_ADDR']` is returned.

```php
use Jasny\HttpMessage\ServerRequest;

$request = (new ServerRequest())->withGlobalEnvironment();
$request->getAttribute('client_ip'); // always returns $_SERVER['REMOTE_ADDR']
```

You can specificy an IP or CIDR address for trusted proxies. When used, addresses send as HTTP header through
`X-Forwarded-For`, `Client-Ip` or [`Forwarded`](https://tools.ietf.org/html/rfc7239) are taken into consideration.

```php
use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\DerivedAttribute\ClientIp;

$request = (new ServerRequest())
    ->withGlobalEnvironment()
    ->withAttribute('client_ip', new ClientIp(['trusted_proxy => '10.0.0.0/24']);

$ip = $request->getAttribute('client_ip'); // for a request from the internal network, use the `X-Forwarded-For` header
```

Note: If more than one of these headers are set, a `RuntimeException` is thrown. This prevents a user injecting a
`Client-Ip` address to fake his ip, where your proxy is setting the `X-Forwarded-For` header. To make sure this
exception doesn't occur, remove all unexpected forward headers.

```php
use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\DerivedAttribute\ClientIp;

$request = (new ServerRequest())
    ->withGlobalEnvironment()
    ->withoutHeader('Client-Ip')
    ->withoutHeader('Forwarded')
    ->withAttribute('client_ip', new ClientIp(['trusted_proxy => '10.0.0.0/24']);
```

#### IsXhr

Test is the request with made using AJAX.

All modern browsers set the `X-Requested-With` header to `XMLHttpRequest` when making an AJAX request. This derived
attribute simply checks that header.

```php
use Jasny\HttpMessage\ServerRequest;

$request = (new ServerRequest())->withGlobalEnvironment();
$isXhr = $request->getAttribute('is_xhr'); // true or false
```

#### LocalReferer

Return the path of the `Referer` header, but only if the referer's scheme, host and port matches request's scheme, host
and port.

```php
use Jasny\HttpMessage\ServerRequest;

$request = (new ServerRequest())->withGlobalEnvironment();
$back = $request->getAttribute('local_referer') ?: '/'; // Referer Uri path, defaults to `/` for no or external referer
```

It is possible to disable the check on scheme and/or port if needed.

```php
use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\DerivedAttribute\LocalReferer;

$request = (new ServerRequest())
    ->withGlobalEnvironment()
    ->withAttribute('local_referer', new LocalReferer(['checkScheme' => false, 'checkPort' => false]));
```

## Testing

When testing code that is fully PSR-7 compatible, create a `ServerRequest` with specific headers, parameters and data
and a default `Response`.

```php
$request = (new ServerRequest())
    ->withMethod('GET')
    ->withUri('/foo')
    ->withQueryParams(['page' => 1]);
```

PSR-7 compatible code MUST NOT access superglobals directly and also MUST NOT output headers and data directly.

### Testing legacy code

This library allows you to test code that isn't fully PSR-7 compatible. It might access the superglobals directly and/or
output using `echo` and `headers()`.

```php
// Start output buffering, so the output isn't send directly
ob_start();

// Create response with (actual) global enviroment. Modifying it, modifies the superglobals.
$request = (new ServerRequest())->withGlobalEnvironment()
    ->withServerParams(['REQUEST_METHOD' => 'GET', 'PATH_INFO' => '/foo'])
    ->withQueryParams(['page' => 1]);

// Create response with (actual) global enviroment.
$response = (new Response())->withGlobalEnvironment();

// Some PSR-7 compatible router handles the request. The code uses `header` and `echo` to output.
$router->route($request, $response);

// Disconnect the global environment, copy the data and headers
$response = $response->withoutGlobalEnviroment();

// Remove all headers and output
header_remove(); 
ob_end_clean();

// Assert response
...
```

Using this technique allows you to start using PSR-7 without having to rewrite your whole code base. Instead you can
refactor your code bit by bit.
