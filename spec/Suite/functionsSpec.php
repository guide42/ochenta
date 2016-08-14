<?php

use Kahlan\Plugin\Monkey;
use ochenta\ServerRequest;
use ochenta\Response;
use function ochenta\resource_of;
use function ochenta\mimetype_of;
use function ochenta\hash;
use function ochenta\emit;
use function ochenta\responder_of;
use function ochenta\stack;
use function ochenta\header;
use function ochenta\append;
use function ochenta\escape;

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
            expect($headers)->toBe(['CONTENT-TYPE' => ['text/html; charset=utf-8']]);
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

describe('escape', function() {
    it('returns empty string when is input is null', function() {
        expect(escape(NULL, 'any', 'any'))->toBe('');
    });

    it('returns same input when is a digit', function() {
        expect(escape('123', 'any', 'any'))->toBe('123');
    });

    it('returns string version of an int', function() {
        expect(escape(123, 'any', 'any'))->toBe('123');
    });

    it('returns escaped html', function() {
        $chars = ['\'' => '&#039;', '"' => '&quot;', '<' => '&lt;', '>' => '&gt;', '&' => '&amp;'];
        foreach ($chars as $char => $encoded) {
            expect(escape($char, 'html', 'UTF-8'))->toBe($encoded);
        }
    });

    it('returns escaped css', function() {
        $chars = ['\'' => '\\27 ', '"' => '\\22 ', '<' => '\\3C ', '>' => '\\3E ', '&' => '\\26 '];
        foreach ($chars as $char => $encoded) {
            expect(escape($char, 'css', 'UTF-8'))->toBe($encoded);
        }
    });

    it('returns escaped css on chars beyond ascii value 255', function() {
        expect(escape('☃', 'css', 'UTF-8'))->toBe('\\E2 ');
    });

    it('returns unescaped css on alphanum chars', function() {
        foreach (['aA', 'zZ', 'E2'] as $char) {
            expect(escape($char, 'css', 'UTF-8'))->toBe($char);
        }
    });

    it('returns escaped js', function() {
        $chars = ['\'' => '\\x27', '"' => '\\x22', '<' => '\\x3C', '>' => '\\x3E', '&' => '\\x26'];
        foreach ($chars as $char => $encoded) {
            expect(escape($char, 'js', 'UTF-8'))->toBe($encoded);
        }
    });

    it('returns escaped js on chars beyond ascii value 255', function() {
        expect(escape('☃', 'js', 'UTF-8'))->toBe('\\xE2');
    });

    it('returns unescaped js on alphanum chars', function() {
        foreach (['aA', 'zZ', 'E2'] as $char) {
            expect(escape($char, 'js', 'UTF-8'))->toBe($char);
        }
    });

    it('uses raw input when is utf-8 and encoding not given', function() {
        expect(escape('☃'))->toBe('☃');
    });

    xit('uses iconv when available to transform input to utf-8', function() {
        Monkey::patch('function_exists', function($fn) {
            return $fn === 'iconv';
        });

        Monkey::patch('iconv', function($from, $to, $str) {
            expect($from)->toBe('UTF-16');
            expect($to)->toBe('UTF-8');
        });

        // U+10437 in UTF-16
        $char = chr(1101) . chr(1000)
              . chr(0000) . chr(0001)
              . chr(1101) . chr(1100)
              . chr(0011) . chr(0111);

        expect(escape($char, 'js', 'UTF-16'))->toBe('\\xEE\\xC4\\xE4\\xE4');
    });

    xit('uses mb_convert_encoding when available to transform input to utf-8', function() {
        Monkey::patch('function_exists', function($fn) {
            return $fn === 'mb_convert_encoding';
        });

        Monkey::patch('mb_convert_encoding', function($str, $to, $from) {
            expect($from)->toBe('UTF-16');
            expect($to)->toBe('UTF-8');
        });

        // U+10437 in UTF-16
        $char = chr(1101) . chr(1000)
              . chr(0000) . chr(0001)
              . chr(1101) . chr(1100)
              . chr(0011) . chr(0111);

        expect(escape($char, 'js', 'UTF-16'))->toBe('\\xEE\\xC4\\xE4\\xE4');
    });

    xit('throws InvalidArgumentException when unknown encoding couldn\'t be transformed into utf-8', function() {
        Monkey::patch('function_exists', function() {
            return FALSE;
        });

        // U+10437 in UTF-16
        $char = chr(1101) . chr(1000)
              . chr(0000) . chr(0001)
              . chr(1101) . chr(1100)
              . chr(0011) . chr(0111);

        expect(function() use($char) { escape($char, 'js', 'UTF-16'); })->toThrow(new InvalidArgumentException);
    });

    it('throws InvalidArgumentException on unknown type', function() {
        expect(function() { escape('Hello World', 'foo', 'UTF-8'); })->toThrow(new InvalidArgumentException);
    });
});
