<?php

use Kahlan\Plugin\Monkey;
use Ochenta\Server\Gateway;
use Ochenta\ServerRequest;

describe('Server\\Gateway', function() {
    describe('->__invoke', function() {
        it('throws RuntimeException when headers has already been sent', function() {
            Monkey::patch('headers_sent', function() {
                return true;
            });

            $server = new Gateway(function(ServerRequest $req, callable $open) {
                $open(200, []);
            });

            expect(function() use($server) { $server(new ServerRequest); })->toThrow(new RuntimeException);
        });

        it('emit response to beyond', function() {
            Monkey::patch('header', function(string $header, bool $replace=true) {
                static $first = true;
                static $reset = true;
                if ($first) {
                    expect($header)->toBe('HTTP/1.1 202');
                    $first = false;
                } else {
                    expect($replace)->toBe($reset);
                    $reset = false;
                }
            });

            Monkey::patch('headers_sent', function() {
                return false;
            });

            $server = new Gateway(function(ServerRequest $req, callable $open) {
                $name = $req->getQuery()['name'] ?? 'World';
                $open(202, ['Content-Language' => ['en', 'es']]);
                yield "Hola $name";
            });

            expect(function() use($server) { $server(new ServerRequest); })->toEcho('Hola World');
        });
    });
});