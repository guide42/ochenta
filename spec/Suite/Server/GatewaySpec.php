<?php

use Kahlan\Plugin\Monkey;
use Ochenta\Server\Gateway;
use Ochenta\ServerRequest;

describe('Server\\Gateway', function() {
    describe('->__invoke', function() {
        it('throws RuntimeException when headers has already been sent', function() {
            Monkey::patch('headers_sent', function() {
                return TRUE;
            });

            $server = new Gateway(function(ServerRequest $req, callable $open) {
                $open(200, []);
            });

            expect(function() use($server) { $server(new ServerRequest); })->toThrow(new RuntimeException);
        });

        it('emit response to beyond', function() {
            Monkey::patch('header', function(string $header, bool $replace=TRUE) {
                static $first = TRUE;
                static $reset = TRUE;
                if ($first) {
                    expect($header)->toBe('HTTP/1.1 202');
                    $first = FALSE;
                } else {
                    expect($replace)->toBe($reset);
                    $reset = FALSE;
                }
            });

            Monkey::patch('headers_sent', function() {
                return FALSE;
            });

            $server = new Gateway(function(ServerRequest $req, callable $open) {
                $name = $req->getQuery()['name'] ?? 'World';
                $open(202, ['Content-Language' => ['en', 'es']]);
                yield "Hola $name";
            });

            expect(function() use($server) { $server(new ServerRequest); })->toEcho('Hola World');
        });

        it('calls responder\'s return', function() {
            $use = FALSE;

            Monkey::patch('headers_sent', function() {
                return FALSE;
            });

            $server = new Gateway(function(ServerRequest $req, callable $open) use(&$use) {
                yield '';

                return function() use(&$use) {
                    $use = TRUE;
                };
            });

            $server(new ServerRequest);

            expect($use)->toBe(TRUE);
        });
    });
});