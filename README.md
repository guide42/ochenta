Ochenta: HTTP library
=====================

- HTTP abstractions: use request/response objects instead of superglobals.
- HTTP middlewares: intersect the process of creating a response from a request.
- HTTP responders: actionable views that build responses.

Hello World
-----------

```php
use ochenta\ServerRequest;
use function ochenta\{emit, responder_of};

emit(new ServerRequest, responder_of('Hello World'));
```

Interested? Keep reading.

Usage
-----

```php
$req = new ServerRequest;
```

It could also be created with it's defaults values:

```php
$req = new ServerRequest($_SERVER, $_GET, $_POST, $_FILES, NULL);
```

That's a incoming request.  
Superglobals are available as `$req->getQuery()`, `$req->getParsedBody()` and `$req->getFiles()`.

Then the low-level `Request` abstraction provides many more methods:

- `$req->getMethod()` and `$req->getTarget()` from the request line.
- `$req->getHeaders()` to get all headers and `$req->getHost()` for the normalized domain.
- `$req->getMediaType()` and `$req->getCharset()` from the `Content-Type` header.

There is `Response`, but is no worth using it. What else? Responders.

Responders
----------

When working in SAPI environment, you could define a response (but not a `ochenta\Response` object) with a responder:

```php
function hola(ServerRequest $req, callable $open) {
    $name = $req->getQuery()['name'] ?? 'World';
    $open(200, ['Content-Language' => ['en', 'es']]);
    yield "Hola $name";
}
```

Using a `ochenta\emit` function, the responder could be emitted:

```php
emit(new ServerRequest, @hola);
```

Middlewares
-----------

Use them to wrap your `request -> responder` process. This is what it look like:

```php
function timeit(callable $handler): callable {
    return function(ServerRequest $req, callable $open) use($handler) {
        $time = -microtime(TRUE);
        $res = yield from $handler($req, $open);
        $time += microtime(TRUE);
        yield sprintf("<address>%.7F secs</address>", $time);
        return $res;
    };
}
```

Decorating your app responder:

```php
$app = @hola;
$app = timeit($app);

emit(new ServerRequest, $app);
```

When options are needed, could be wrapped in yet another function.

```php
function add_header(string $name, string $value): callable {
    return function(callable $handler) use($name, $value): callable {
        return function(ServerRequest $req, callable $open) use($name, $value, $handler) {
            return $handler($req, function(int $status, array $headers) use($name, $value, $open) {
                $headers[$name] = [$value];
                $open($status, $headers);
            });
        };
    };
}
```

Complex? This middleware exists at `ochenta\header`. This is how to use it:

```php
$app = add_header('X-Frame-Options', 'SAMEORIGIN')($app);
```

What a hassle! Better use `ochenta\stack` to do stacks of middlewares:

```php
$app = stack(@hola, [
    add_header('X-Xss-Protection', '1; mode=block'),
    add_header('X-Frame-Options', 'SAMEORIGIN'),
    @timeit,
]);
```

You got this far. Look at [readme.php](readme.php) to see the complete example.

API
---

```php
responder_of(Response $resource)                             // creates a responder from a Response
responder_of(resource $resource)                             // ... from a resource
responder_of(scalar $resource)                               // ... from content

emit(ServerRequest $req, callable $handler)                  // emits a responder

stack(callable $responder, array $stack)                     // expects stack items to be a function(callable $next)
stack(callable $responder, callable $resolver, array $stack) // ... use resolver as function(callable $prev, $handler)

// MIDDLEWARES

header(string $name, array $values)                          // adds a header to responder
header(string $name, string $value)                          // ... with single value

append(string $content)                                      // adds content before body
append(string $content, string $tag)                         // ... before every given tag

// RESPONDERS

redirect(string $uri)                                        // redirect to the given url
redirect(string $uri, int $statusCode)                       // ... with given status code

// HELPERS

stream_of(scalar $resource)                                  // creates tmp file with $resouce content
```

Badges
------

[![Latest Stable Version](https://poser.pugx.org/guide42/ochenta/v/stable.svg)](https://packagist.org/packages/guide42/ochenta)
[![Build Status](https://travis-ci.org/guide42/ochenta.svg?branch=master)](https://travis-ci.org/guide42/ochenta)
[![Coverage Status](https://coveralls.io/repos/github/guide42/ochenta/badge.svg)](https://coveralls.io/github/guide42/ochenta)
