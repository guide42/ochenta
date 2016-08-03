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

Using a `Ochenta\Server\Gateway` the responder could be emitted:

```php
use Ochenta\ServerRequest;
use Ochenta\Server\Gateway;

$server = new Gateway(@hola);
$server(new ServerRequest);
```

Middlewares
-----------

Use them to wrap your `request -> responder` process. This is what it look like:

```php
function timeit(callable $handler) {
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

$server = new Gateway($app);
$server(new ServerRequest);
```

Badges
------

[![Latest Stable Version](https://poser.pugx.org/guide42/ochenta/v/stable.svg)](https://packagist.org/packages/guide42/ochenta)
[![Build Status](https://travis-ci.org/guide42/ochenta.svg?branch=master)](https://travis-ci.org/guide42/ochenta)
[![Code Coverage](https://scrutinizer-ci.com/g/guide42/ochenta/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/guide42/ochenta/?branch=master)
