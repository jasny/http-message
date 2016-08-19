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

The library has the following classes

 * `ServerRequest` implements `Psr\Http\Message\ServerRequestInterface`
 * `Response` implements `Psr\Http\Message\ResponseInterface`
