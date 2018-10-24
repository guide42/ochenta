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

        it('throws InvalidArgumentException on invalid uri', function() {
            expect(function() {
                new Request('GET', 'http://@/');
            })
            ->toThrow(new InvalidArgumentException);
        });

        it('assigns headers with keys in uppercase', function() {
            $req = new Request('GET', '/', ['Host' => ['example.com'], 'Content-Type' => ['text/plain']]);

            expect($req->getHeaders())->toBeA('array')->toContainKey('HOST')->toContainKey('CONTENT-TYPE');
            expect($req->getHeaders()['HOST'])->toBe(['example.com']);
        });

        it('assigns headers with values as array', function() {
            $req = new Request('GET', '/', ['Host' => 'example.com', 'Content-Type' => 'text/plain']);

            expect($req->getHeaders())->toBeA('array')->toContainKey('HOST');
            expect($req->getHeaders()['HOST'])->toBeA('array')->toContainKey(0)->toBe(['example.com']);
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

    describe('->getHost', function() {
        it('returns hostname from header', function() {
            expect((new Request('GET', '/', ['Host' => 'example.com']))->getHost())->toBe('example.com');
        });

        it('returns hostname from request uri', function() {
            expect((new Request('GET', 'http://example.com/'))->getHost())->toBe('example.com');
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
});