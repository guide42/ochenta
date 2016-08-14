Ochenta: HTTP request/response implementation
=============================================

HTTP abstraction layer in php 7 with [psr-7](http://www.php-fig.org/psr/psr-7/) basic implementation.

This is just a PoC. DO NOT USE IT IN PRODUCTION.

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

That's a request. There is `ochenta\Request` but is not recomended to be used alone as it doesn't normalize any value.

There is `ochenta\Response` but is no worth using it. What else? Responders.

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

Complex? This middleware exists at `ochenta\header`. This is how we use it:

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

API
---

```php
resource_of(scalar $resource)                                // creates tmp file with $resouce content

mimetype_of(resource $resource)                              // tries to find out the mimetype
mimetype_of(resource $resource, string $filename)            // ... optionally with filename
mimetype_of(string $resource)                                // ... of content of $resource
mimetype_of(string $resource, string $filename)              // ... with it's filename

hash(resource $resource)                                     // calculates md5 of resource
hash(resource $resource, string $algo)                       // ... optionally hash with another algorithm
hash(scalar $resource)                                       // ... of $resource content
hash(scalar $resource, string $algo)                         // ... with other algorithm

responder_of(Response $resource)                             // creates a responder from a Response
responder_of(resource $resource)                             // ... from a resource
responder_of(scalar $resource)                               // ... from content

emit(ServerRequest $req, callable $handler)                  // emits a responder

stack(callable $responder, array $stack)                     // expects items to be a function(callable $next)
stack(callable $responder, callable $resolver, array $stack) // ... use resolver as function(callable $prev, $handler)

// MIDDLEWARES

header(string $name, array $values)                          // adds a header to responder
append(string $content)                                      // adds content before body
append(string $content, string $tag)                         // ... before every given tag
```

Badges
------

[![Latest Stable Version](https://poser.pugx.org/guide42/ochenta/v/stable.svg)](https://packagist.org/packages/guide42/ochenta)
[![Build Status](https://travis-ci.org/guide42/ochenta.svg?branch=master)](https://travis-ci.org/guide42/ochenta)
[![Code Coverage](https://scrutinizer-ci.com/g/guide42/ochenta/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/guide42/ochenta/?branch=master)
