# Jasny HTTP Message

This library provides an abstraction around PHPs various superglobals as well as controlling the HTTP response. This
practice helps reduce coupling to the superglobals by consumers, and encourages and promotes the ability to test request
consumers.

The library only implements those [PSR-7 interfaces](http://www.php-fig.org/psr/psr-7/) focussed on handling a received
HTTP request. If you want to send HTTP request to other webservices, I recommend using
[Guzzle](http://docs.guzzlephp.org/).


## Installation

    composer install jasny/http-message


## Documentation

The library implements the following PSR-7 interfaces

 * [`ServerRequest`](#ServerRequest) implements `Psr\Http\Message\ServerRequestInterface`
 * [`Response`](#Response) implements `Psr\Http\Message\ResponseInterface`
 * [`InputStream`](#InputStream) implements `Psr\Http\Message\StreamInterface`
 * [`OutputStream`](#OutputStream) implements `Psr\Http\Message\StreamInterface`
 * [`Uri`](#Uri) implements `Psr\Http\Message\UriInterface`

it defines one interface

 * [`DerivedAttribute`](#DerivedAttribute)

### ServerRequest

For the full documentation about the `ServerRequest`, please see
[PSR-7 `RequestInterface`](http://www.php-fig.org/psr/psr-7/#3-2-psr-http-message-requestinterface) and
[PSR-7 `ServerRequestInterface`](http://www.php-fig.org/psr/psr-7/#3-2-1-psr-http-message-serverrequestinterface).

To create a `ServerRequest` object with the `$_SERVER`, `$_COOKIE`, `$_GET`, `$_POST` and `$_FILES` superglobals and
with `php://input` as input stream, use the `withSuperGlobals()` method.

```php
$request = (new Jasny\HttpMessage\ServerRequest())->withSuperGlobals();
```


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
    ->withAttribute('is_annoying_bot', new DetectBot(['magpie']))
;
```

_Remember that a `ServerRequest` method is immutability, so `withAttribute()` will create a new object._

This library comes with a number of derived attributes, which may be used.

#### ClientIp

Get the client IP. By default only `$_SERVER['REMOTE_ADDR']` is returned.

```php
use Jasny\HttpMessage\ServerRequest;

$request = (new ServerRequest())->withSuperGlobals();
$request->getAttribute('client_ip'); // always returns $_SERVER['REMOTE_ADDR']
```

You can specificy an IP or CIDR address for trusted proxies. When used, addresses send as HTTP header through
`X-Forwarded-For` or `Client-Ip` are taken into consideration.

```php
use Jasny\HttpMessage\ServerRequest;
use Jasny\HttpMessage\DerivedAttribute\ClientIp;

$request = (new ServerRequest())
    ->withSuperGlobals()
    ->withAttribute(new ClientIp('10.0.0.0/24'))
;

$ip = $request->getAttribute('client_ip'); // for a request from the internal network, use the `X-Forwarded-For` header
```

#### IsAjax

Test is the request with made using AJAX.

All modern browsers set the `X-Requested-With` header to `XMLHttpRequest` when making an AJAX request. This derived
attribute simply checks that header.

```php
use Jasny\HttpMessage\ServerRequest;

$request = (new ServerRequest())->withSuperGlobals();
$isAjax = $request->getAttribute('is_ajax'); // true or false
```

#### LocalReferer

Return the path of the `Referer` header, but only if the referer's host part matches the `Host` header.

```php
use Jasny\HttpMessage\ServerRequest;

$request = (new ServerRequest())->withSuperGlobals();
$back = $request->getAttribute('local_referer') ?: '/';
```


### Uri

For the full documentation about the `Uri`, please see
[PSR-7 `UriInterface`](http://www.php-fig.org/psr/psr-7/#3-5-psr-http-message-uriinterface).

When creating an Uri you can pass the URL as string or pass the URL in parts as associative array. For the URL parts
see the [`parse_url`](http://www.php.net/parse_url) function.

The `Jasny\HttpMessage\Uri` object only supports the `http` and `https` schemes.

```php
$uri = new Jasny\HttpMessage\Uri("http://www.example.com/foo");
```
