<?php declare(strict_types=1);

use Kahlan\Plugin\Monkey;
use ochenta\ServerRequest;
use ochenta\Response;
use function ochenta\emit;
use function ochenta\responder_of;
use function ochenta\stack;
use function ochenta\header;
use function ochenta\append;
use function ochenta\stream_of;
use function ochenta\redirect;

describe('responder_of', function() {
    it('throws InvalidArgumentException when an invalid resource is given', function() {
        expect(function() {
            responder_of([]);
        })
        ->toThrow(new InvalidArgumentException);
    });

    it('returns a responder that generates the same one if the resource is a generator', function() {
        $responder0 = function(ServerRequest $req, callable $open) { yield 'Hello World'; };
        $responder1 = responder_of($responder0(new ServerRequest, function() {}));
        $generator = $responder1(new ServerRequest, function() {});

        expect(iterator_to_array($generator, false))->toBe(['Hello World']);
    });

    it('returns a responder that generates an empty string if the resource is null', function() {
        $responder = responder_of(NULL);
        $generator = $responder(new ServerRequest, function() {});

        expect(iterator_to_array($generator, false))->toBe(['']);
    });

    it('returns a responder that generates the given string', function() {
        $responder = responder_of('Hello World');
        $generator = $responder(new ServerRequest, function() {});

        expect(iterator_to_array($generator, false))->toBe(['Hello World']);
    });

    it('returns a responder that generates the given resource by reading from the current pointer', function() {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'Hello World');
        fseek($resource, 0, SEEK_SET);

        $responder = responder_of($resource);
        $generator = $responder(new ServerRequest, function() {});

        expect(iterator_to_array($generator, false))->toBe(['Hello World', '']);
    });

    it('returns a responder that generates the given response', function() {
        $response = new Response(202, ['Content-Type' => ['text/html']], 'Hello World');
        $responder = responder_of($response);
        $generator = $responder(new ServerRequest, function(int $status, array $headers) {
            expect($status)->toBe(202);
            expect($headers)->toContainKey('CONTENT-TYPE');
            expect($headers['CONTENT-TYPE'])->toBe(['text/html; charset=utf-8']);
        });

        expect(iterator_to_array($generator, false))->toBe(['Hello World','']);
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
    it('emit response to beyond', function() {
        allow('header', function(string $header, bool $replace=TRUE) {
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

        allow('headers_sent', function() {
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

describe('append', function() {
    it('adds given content to response before body', function() {
        $handler = function(ServerRequest $req, callable $open) { yield '<body>BODY:</body>'; };
        $midware = append('HERE')($handler);

        expect(iterator_to_array($midware(new ServerRequest, function() {}), false))->toBe(['<body>BODY:', 'HERE', '</body>']);
    });

    it('adds given content before given tag', function() {
        $handler = function(ServerRequest $req, callable $open) { yield '<head><meta></head>'; };
        $midware = append('<style></style>', 'head')($handler);

        expect(iterator_to_array($midware(new ServerRequest, function() {}), false))->toBe(['<head><meta>', '<style></style>', '</head>']);
    });

    it('adds nothing if close tag not found', function() {
        $handler = function(ServerRequest $req, callable $open) { yield '<p>'; };
        $midware = append(':)', 'p')($handler);

        expect(iterator_to_array($midware(new ServerRequest, function() {}), false))->toBe(['<p>']);
    });
});

describe('redirect', function() {
    it('throws InvalidArgumentException on invalid uri given', function() {
        expect(function() { redirect('http:///ochenta'); })->toThrow(new InvalidArgumentException);
    });

    it('throws InvalidArgumentException on invalid status code given', function() {
        expect(function() { redirect('/', 100); })->toThrow(new InvalidArgumentException);
    });

    it('opens with status code', function() {
        $responder = redirect('http://ochenta/', 301);
        $responder(new ServerRequest, function(int $status, array $headers) {
            expect($status)->toBe(301);
        });
    });

    it('opens with status code 302 if none given', function() {
        $responder = redirect('http://ochenta/');
        $responder(new ServerRequest, function(int $status, array $headers) {
            expect($status)->toBe(302);
        });
    });

    it('opens with location header with given uri', function() {
        $responder = redirect('http://ochenta/');
        $responder(new ServerRequest, function(int $status, array $headers) {
            expect($headers)->toBe(['Location' => ['http://ochenta/']]);
        });
    });

    it('opens with location header with uri and inherit scheme/host information from request', function() {
        $req = new ServerRequest(['HTTP' => 'off', 'HTTP_HOST' => 'ochenta:8080']);
        $responder = redirect('/');
        $responder($req, function(int $status, array $headers) {
            expect($headers)->toBe(['Location' => ['http://ochenta:8080/']]);
        });
    });
});

describe('stream_of', function() {
    it('returns null when null given', function() {
        expect(stream_of(NULL))->toBeNull();
    });
});
