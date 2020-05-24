<?php declare(strict_types=1);

use ochenta\Request;

describe('Request', function() {
    describe('->__construct', function() {
        it('assigns method in uppercase', function() {
            expect((new Request('get', '/', ['Host' => 'example.com']))->getMethod())->toBe('GET');
            expect((new Request('gET', '/', ['Host' => 'example.com']))->getMethod())->toBe('GET');
        });

        it('assigns uri from parse_url array', function() {
            $uri = [
                'scheme' => 'http',
                'host'   => 'example.com',
                'path'   => '/path',
                'query'  => '?queryString',
            ];

            expect((new Request('GET', $uri))->getUri())->toBeA('array')->toContainKey('host');
        });

        it('assigns uri from array keeping only the keys that are valid result of parse_url', function() {
            $req = new Request('GET', [
                'scheme'     => 'http',
                'host'       => 'example.com',
                'controller' => 'view_user',
                'user_id'    => 123,
            ]);

            expect($req->getUri())->toBe(['scheme' => 'http', 'host' => 'example.com']);
        });

        it('throws InvalidArgumentException on invalid uri', function() {
            expect(function() {
                new Request('GET', 'http://@/');
            })
            ->toThrow(new InvalidArgumentException);
        });

        it('throws InvalidArgumentException on non-string uri', function() {
            expect(function() { new Request('GET', 1.42, ['Host' => 'example.com']); })->toThrow(new InvalidArgumentException);
            expect(function() { new Request('GET', TRUE, ['Host' => 'example.com']); })->toThrow(new InvalidArgumentException);
        });

        it('throws InvalidArgumentException on invalid array uri', function() {
            expect(function() {
                new Request('GET', ['foo' => 'bar'], ['Host' => 'example.com']);
            })
            ->toThrow(new InvalidArgumentException);
        });

        it('assigns headers with keys in uppercase', function() {
            $req = new Request('GET', '/', ['Host' => ['example.com'], 'Content-Type' => ['text/plain']]);

            expect($req->getHeaders())->toBeA('array')->toContainKey('HOST')->toContainKey('CONTENT-TYPE');
            expect($req->getHeaders()['HOST'])->toBe(['example.com']);
        });

        it('assigns headers with single value into an array of values', function() {
            $req = new Request('GET', '/', ['Host' => 'example.com', 'Content-Type' => 'text/plain']);

            expect($req->getHeaders())->toBeA('array')->toContainKey('HOST');
            expect($req->getHeaders()['HOST'])->toBeA('array')->toContainKey(0)->toBe(['example.com']);
        });

        it('assigns headers with trimmed values', function() {
            $req = new Request('GET', '/', ['Host' => '  example.com  ', 'Content-Type' => "\ttext/plain\n"]);

            expect($req->getHeaders()['HOST'])->toBe(['example.com']);
            expect($req->getHeaders()['CONTENT-TYPE'])->toBe(['text/plain']);
        });

        it('assigns headers with values as string', function() {
            $req = new Request('GET', '/', ['Host' => 'example.com', 'Content-Length' => 0, 'X-Hits' => [2, 42]]);

            expect($req->getHeaders()['X-HITS'][0])->toBeA('string');
            expect($req->getHeaders()['X-HITS'][1])->toBeA('string');
            expect($req->getHeaders()['CONTENT-LENGTH'][0])->toBeA('string');
        });

        it('assigns host header from uri', function() {
            $req = new Request('GET', 'http://example.com/path?queryString');

            expect($req->getHeaders())->toBeA('array')->toContainKey('HOST');
            expect($req->getHeaders()['HOST'])->toBe(['example.com']);
        });

        it('assigns body, defaults to null', function() {
            expect((new Request('GET', '/', ['Host' => 'example.com']))->getBody())->toBeNull();
        });

        it('assigns body as resource if given is scalar', function() {
           expect((new Request('GET', '/', ['Host' => 'example.com'], 'Hello World'))->getBody())->toBeA('resource');
        });

        it('throws InvalidArgumentException on missing host header', function() {
            expect(function() {
                new Request('GET', '/');
            })
            ->toThrow(new InvalidArgumentException);
        });

        it('throws InvalidArgumentException on invalid body', function() {
            expect(function() {
                new Request('GET', '/', ['Host' => 'example.com'], []);
            })
            ->toThrow(new InvalidArgumentException);
        });
    });

    describe('->isMethod', function() {
        it('returns true when request method matches', function() {
            expect((new Request('GET', 'https://example.com/'))->isMethod('GET'))->toBe(TRUE);
        });

        it('compares method case insensitive', function() {
            expect((new Request('GET', 'https://example.com/'))->isMethod('Get'))->toBe(TRUE);
        });
    });

    describe('->isSecure', function() {
        it('returns true when request uri schema is https', function() {
            expect((new Request('GET', 'https://example.com/'))->isSecure())->toBe(TRUE);
        });

        it('returns false when request uri schema is http', function() {
            expect((new Request('GET', 'http://example.com/'))->isSecure())->toBe(FALSE);
        });

        it('returns false when request uri schema is not defined', function() {
            expect((new Request('GET', '/', ['Host' => 'example.com']))->isSecure())->toBe(FALSE);
        });
    });

    describe('->getHost', function() {
        it('returns hostname from header', function() {
            expect((new Request('GET', '/', ['Host' => 'example.com']))->getHost())->toBe('example.com');
        });

        it('returns hostname from request uri', function() {
            expect((new Request('GET', 'http://example.com/'))->getHost())->toBe('example.com');
        });

        it('returns hostname in lowercase', function() {
            expect((new Request('GET', '/', ['Host' => 'ExAmPlE.com']))->getHost())->toBe('example.com');
        });
    });

    describe('->getTargetPath', function() {
        it('return forward slash when path component of the uri is not defined', function() {
            expect((new Request('GET', '?queryString', ['Host' => 'example.com']))->getTargetPath())->toBe('/');
        });

        it('return path of the uri', function() {
            expect((new Request('GET', 'http://example.com/path?queryString'))->getTargetPath())->toBe('/path');
        });
    });

    describe('->getTarget', function() {
        it('return forward slash when path component of the uri is not defined', function() {
            expect((new Request('GET', '?queryString', ['Host' => 'example.com']))->getTarget())->toBe('/?queryString');
        });

        it('return path and query string of the uri', function() {
            expect((new Request('GET', 'http://example.com/path?queryString'))->getTarget())->toBe('/path?queryString');
        });
    });

    describe('->getMediaType', function() {
        it('returns null when Content-Type is not defined', function() {
            expect((new Request('GET', '/', ['Host' => 'example.com']))->getMediaType())->toBeNull();
        });

        it('returns Content-Type header in lowercase', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => ['TEXT/PLAIN']
            ]);
            expect($req->getMediaType())->toBe('text/plain');
        });

        it('returns Content-Type header without charset', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => ['text/plain;charset=ISO-8859-4']
            ]);
            expect($req->getMediaType())->toBe('text/plain');
        });

        it('returns same value on multiple calls', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => ['text/plain;charset=ISO-8859-4']
            ]);
            expect($req->getMediaType())->toBe('text/plain');
            expect($req->getMediaType())->toBe('text/plain');
        });
    });

    describe('->getCharset', function() {
        it('returns null when Content-Type is not defined', function() {
            expect((new Request('GET', '/', ['Host' => 'example.com']))->getCharset())->toBeNull();
        });

        it('returns null when Content-Type charset is not given', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => ['text/plain;important=yes']
            ]);
            expect($req->getCharset())->toBeNull();
        });

        it('returns normalized Content-Type charset', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => ['text/plain;CHARSET="UTF-8"']
            ]);
            expect($req->getCharset())->toBe('utf-8');
        });

        it('returns same value on multiple calls', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => ['text/plain;CHARSET="UTF-8"']
            ]);
            expect($req->getCharset())->toBe('utf-8');
            expect($req->getCharset())->toBe('utf-8');
        });
    });

    describe('->isForm', function() {
        it('returns true when Content-Type is application/x-www-form-urlencoded', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => ['application/x-www-form-urlencoded']
            ]);
            expect($req->isForm())->toBe(TRUE);
        });

        it('returns true when Content-Type is multipart/form-data', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => ['multipart/form-data']
            ]);
            expect($req->isForm())->toBe(TRUE);
        });

        it('returns false when Content-Type is text/plain', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => ['text/plain']
            ]);
            expect($req->isForm())->toBe(FALSE);
        });
    });

    describe('->isJSON', function() {
        it('returns true when Content-Type is application/json', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => 'application/json'
            ]);
            expect($req->isJSON())->toBe(TRUE);
        });

        it('returns true when Content-Type is application/x-json', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => 'application/x-json'
            ]);
            expect($req->isJSON())->toBe(TRUE);
        });

        it('returns true when Content-Type is application/ld+json', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => 'application/ld+json'
            ]);
            expect($req->isJSON())->toBe(TRUE);
        });

        it('returns false when Content-Type is text/plain', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Content-Type' => 'text/plain'
            ]);
            expect($req->isJSON())->toBe(FALSE);
        });
    });

    describe('->isAJAX', function() {
        it('returns true when X-Requested-With is XMLHttpRequest', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'X-Requested-With' => 'XMLHttpRequest'
            ]);
            expect($req->isAJAX())->toBe(TRUE);
        });

        it('returns false when X-Requested-With is not XMLHttpRequest', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'X-Requested-With' => 'JSONHttpRequest'
            ]);
            expect($req->isAJAX())->toBe(FALSE);
        });
    });

    describe('->getAccept', function() {
        it('returns content types from Accept header parsed into an associative array', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Accept' => 'text/plain; q=0.5, text/html'
            ]);
            expect($req->getAccept())->toBeAn('array')->toContainKey('text/html');
            expect($req->getAccept()['text/plain'])->toBeAn('array')->toContainKey('q');
            expect($req->getAccept()['text/plain']['q'])->toEqual(0.5);
        });

        it('returns content types from Accept header sorted by quality attribute', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Accept' => 'text/plain; q=0.5, application/json, text/html; q=0.8'
            ]);
            $contentTypes = array_keys($req->getAccept());
            expect($contentTypes)->toBeAn('array')->toHaveLength(3);
            expect(array_shift($contentTypes))->toEqual('application/json');
            expect(array_shift($contentTypes))->toEqual('text/html');
            expect(array_shift($contentTypes))->toEqual('text/plain');
        });

        it('returns content types from Accept header sorted by index when quality attribute is the same', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Accept' => 'text/plain; q=0.8, application/json, text/html; q=0.8'
            ]);
            $contentTypes = array_keys($req->getAccept());
            expect($contentTypes)->toEqual(['application/json', 'text/plain', 'text/html']);
        });

        it('returns content types from all Accept headers joined and parsed together', function() {
            $req = new Request('GET', '/', [
                'Host' => 'example.com',
                'Accept' => ['text/plain; q=0.5', 'text/html'],
            ]);
            expect($req->getAccept())->toBeAn('array')->toContainKey('text/html');
        });

        it('returns NULL when Accept header is not found', function() {
            $req = new Request('GET', '/', ['Host' => 'example.com']);
            expect($req->getAccept())->toBe(NULL);
        });
    });
});