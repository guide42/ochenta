<?php

use Kahlan\Plugin\Monkey;
use Ochenta\ServerRequest;
use function Ochenta\resource_of;
use function Ochenta\mimetype_of;
use function Ochenta\hash;
use function Ochenta\emit;
use function Ochenta\stack;
use function Ochenta\header;

describe('resource_of', function() {
    it('returns null when null given', function() {
        expect(resource_of(NULL))->toBeNull();
    });
});

describe('mimetype_of', function() {
    it('throws RuntimeException when null given', function() {
        expect(function() { mimetype_of(NULL); })->toThrow(new RuntimeException);
    });

    // Disabled because Monkey::patch doesn't work on included files
    xit('throws RuntimeException when finfo db is not available', function() {
        Monkey::patch('finfo_open', function() {
            return FALSE;
        });

        expect(function() { mimetype_of('Hello World'); })->toThrow(new RuntimeException);
    });

    it('returns mimetype from fileinfo db if the file is found', function() {
        $tmpfile = tempnam(sys_get_temp_dir(), 'kahlan/');
        skipIf($tmpfile === FALSE);
        $tmphandle = fopen($tmpfile, 'a');
        fwrite($tmphandle, 'Hello World');
        fclose($tmphandle);
        expect(mimetype_of(NULL, $tmpfile))->toBe('text/plain');
        unlink($tmpfile);
    });

    it('returns deduced mimetype of string', function() {
        expect(mimetype_of('Hello World'))->toBe('text/plain');
        expect(mimetype_of("\xff\xd8\xffbla.txt"))->toBe('image/jpeg');
    });

    it('returns deduced mimetype of resource', function() {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'Hello World');
        expect(mimetype_of($resource))->toBe('text/plain');
        fclose($resource);
    });
});

describe('hash', function() {
    it('throws InvalidArgumentException when null given', function() {
        expect(function() { hash(NULL); })->toThrow(new InvalidArgumentException);
    });

    it('throws InvalidArgumentException when the given resource is not seekable', function() {
        expect(function() { hash(popen('php --version', 'r')); })->toThrow(new InvalidArgumentException);
    });

    it('returns hash of an scalar', function() {
        expect(hash(42))->toBe('a1d0c6e83f027327d8461063f4ac58a6');
        expect(hash('Hello World'))->toBe('b10a8db164e0754105b7a99be72e3fe5');
    });

    it('returns hash of a resource', function() {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'Hello World');
        expect(hash($resource))->toBe('b10a8db164e0754105b7a99be72e3fe5');
    });
});

describe('emit', function() {
    it('throws RuntimeException when headers has already been sent', function() {
        // This patch is not being executed, but because kahlan already print
        // tests to console, headers_sent returns true.
        Monkey::patch('headers_sent', function() {
            return TRUE;
        });

        expect(function() {
            emit(new ServerRequest, function(ServerRequest $req, callable $open) {
                $open(200, []);
            });
        })
        ->toThrow(new RuntimeException);
    });

    // Disabled because Monkey::patch doesn't work on included files
    xit('emit response to beyond', function() {
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

        expect(function() {
            emit(new ServerRequest, function(ServerRequest $req, callable $open) {
                $name = $req->getQuery()['name'] ?? 'World';
                $open(202, ['Content-Language' => ['en', 'es']]);
                yield "Hola $name";
            });
        })
        ->toEcho('Hola World');
    });

    it('calls responder\'s return', function() {
        $closed = FALSE;

        emit(new ServerRequest, function(ServerRequest $req, callable $open) use(&$closed) {
            yield '';

            return function() use(&$closed) {
                $closed = TRUE;
            };
        });

        expect($closed)->toBe(TRUE);
    });
});

describe('stack', function() {
    it('throws InvalidArgumentException if resolver is not callable', function() {
        expect(function() {
            stack(function() {}, TRUE);
        })
        ->toThrow(new InvalidArgumentException);
    });

    it('throws InvalidArgumentException when no middleware was given', function() {
        expect(function() {
            stack(function() {}, function() {});
        })
        ->toThrow(new InvalidArgumentException);
    });

    given('res', function() {
        return function(ServerRequest $req, callable $open) {
            $open(202, ['Content-Language' => ['en', 'es']]);
            yield 'Hola Mundo';
        };
    });

    it('calls resolver with responder and middleware', function() {
        $responder = function() {};
        $middleware = function() {};

        $resolver = function($prev, $handler) use($responder, $middleware) {
            expect($prev)->toBe($responder);
            expect($handler)->toBe($middleware);
        };

        stack($responder, $resolver, $middleware);
    });

    it('calls resolver with inverted middleware list', function() {
        $middle0 = function() {};
        $middle1 = function() {};

        $resolver = function($prev, $handler) use($middle0, $middle1) {
            static $middlewares = 1;
            expect($handler)->toBe(${'middle' . $middlewares--});
        };

        stack(function() {}, $resolver, $middle0, $middle1);
    });

    it('calls resolver with flatten middleware list', function() {
        $middle0 = function() {};
        $middle1 = function() {};
        $middle2 = function() {};

        $resolver = function($prev, $handler) use($middle0, $middle1, $middle2) {
            static $middlewares = 2;
            expect($handler)->toBe(${'middle' . $middlewares--});
        };

        stack(function() {}, $resolver, $middle0, [$middle1, $middle2]);
    });

    it('uses default resolver when a stack of middlewares is given instead', function() {
        $responder = function() {};

        $handler1 = function() {};
        $middle1 = function($handler) use($responder, $handler1) {
            expect($handler)->toBe($responder);
            return $handler1;
        };

        $handler2 = function() {};
        $middle0 = function($handler) use($handler1, $handler2) {
            expect($handler)->toBe($handler1);
            return $handler2;
        };

        expect(stack($responder, [$middle0, $middle1]))->toBe($handler2);
    });

    it('throws InvalidArgumentException by default resolver when middleware is not a callable', function() {
        expect(function() {
            stack(function() {}, [FALSE]);
        })
        ->toThrow(new InvalidArgumentException);
    });
});

describe('header', function() {
    it('adds the given header with string value', function() {
        $midware = header('X-Frame-Options', 'SAMEORIGIN');
        $handler = $midware(function(ServerRequest $req, callable $open) {
            $open(200, []);
        });

        $handler(new ServerRequest, function(int $status, array $headers) {
            expect($headers)->toBe(['X-Frame-Options' => ['SAMEORIGIN']]);
        });
    });

    it('adds the given header with variadics values', function() {
        $midware = header('Content-Language', 'en', 'es');
        $handler = $midware(function(ServerRequest $req, callable $open) {
            $open(200, []);
        });

        $handler(new ServerRequest, function(int $status, array $headers) {
            expect($headers)->toBe(['Content-Language' => ['en', 'es']]);
        });
    });

    it('replaces if header already exists', function() {
        $midware = header('Content-Type', 'text/plain');
        $handler = $midware(function(ServerRequest $req, callable $open) {
            $open(200, ['Content-Type' => ['text/html']]);
        });

        $handler(new ServerRequest, function(int $status, array $headers) {
            expect($headers)->toBe(['Content-Type' => ['text/plain']]);
        });
    });
});
