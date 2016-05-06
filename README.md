# Jasny/HTTP-Message

This library provides an abstraction around PHPs various superglobals as well as controlling the HTTP response. This practice helps reduce coupling to the superglobals by consumers, and encourages and promotes the ability to test request consumers.

## Installation

    composer install jasny/http-message

## Documentation

The library has the following interfaces

 * `ServerRequest` extends `Psr\Http\Message\ServerRequestInterface`
 * `Response` extends `Psr\Http\Message\ResponseInterface`

and the following classes

 * `RealRequest` implements `ServerRequest`
 * `MockRequest` implements `ServerRequest`
 * `RealResponse` implements `Response`
 * `MockResponse` implements `Response`

