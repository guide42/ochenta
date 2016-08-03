Ochenta: HTTP request/response implementation
=============================================

HTTP abstraction layer in php 7 with [psr-7](http://www.php-fig.org/psr/psr-7/) basic implementation.

This is just a PoC. DO NOT USE IT IN PRODUCTION.

Usage
-----

```php
$req = new Ochenta\ServerRequest;
```

It could also be created with it's defaults values:

```php
$req = new Ochenta\ServerRequest($_SERVER, $_GET, $_POST, $_FILES, NULL);
```

That's a request. There is `Ochenta\Request` but is not recomended to be used alone as it doesn't normalize any value.

There is `Ochenta\Response` but is no worth using it. What else? Responders.

Responders
----------

When working in SAPI environment, you could define a response (but not a `Ochenta\Response` object) with a responder:

```php
function hola(ServerRequest $req, callable $open) {
    $name = $req->getQuery()['name'] ?? 'World';
    $open(200, ['Content-Language' => ['en', 'es']]);
    yield "Hola $name";
}
```

Using a `Ochenta\emit` function, the responder could be emitted:

```php
Ochenta\emit(new Ochenta\ServerRequest, @hola);
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
use Ochenta\ServerRequest;
use function Ochenta\emit;

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

Complex? This middleware exists at `Ochenta\header`. This is how we use it:

```php
$app = add_header('X-Frame-Options', 'SAMEORIGIN')($app);
```

What a hassle! Better to do stacks of middlewares:

```php
$app = Ochenta\stack(@hola, [
    add_header('X-Xss-Protection', '1; mode=block'),
    add_header('X-Frame-Options', 'SAMEORIGIN'),
    @timeit,
]);
```

Badges
------

[![Latest Stable Version](https://poser.pugx.org/guide42/ochenta/v/stable.svg)](https://packagist.org/packages/guide42/ochenta)
[![Build Status](https://travis-ci.org/guide42/ochenta.svg?branch=master)](https://travis-ci.org/guide42/ochenta)
[![Code Coverage](https://scrutinizer-ci.com/g/guide42/ochenta/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/guide42/ochenta/?branch=master)
