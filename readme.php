<?php
# php -S 127.0.0.1:8080 readme.php

include __DIR__ . '/vendor/autoload.php';

use ochenta\ServerRequest;
use function ochenta\{emit, stack};

function hola(ServerRequest $req, callable $open) {
    $name = $req->getQuery()['name'] ?? 'World';
    $open(200, ['Content-Language' => ['en', 'es']]);
    yield "Hola $name";
}

function timeit(callable $handler): callable {
    return function(ServerRequest $req, callable $open) use($handler) {
        $time = -microtime(TRUE);
        $res = yield from $handler($req, $open);
        $time += microtime(TRUE);
        yield sprintf("<address>%.7F secs</address>", $time);
        return $res;
    };
}

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

$app = stack(@hola, [
    add_header('X-Xss-Protection', '1; mode=block'),
    add_header('X-Frame-Options', 'SAMEORIGIN'),
    @timeit,
]);

emit(new ServerRequest, $app);