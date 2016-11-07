<?php declare(strict_types=1);

use ochenta\Request;

describe('Request', function() {
    describe('->__construct', function() {
        it('assigns method in uppercase', function() {
            expect((new Request('get', '/'))->getMethod())->toBe('GET');
            expect((new Request('gET', '/'))->getMethod())->toBe('GET');
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

        it('assigns host header from uri', function() {
            $req = new Request('GET', 'http://example.com/path?queryString');

            expect($req->getHeaders())->toBeA('array')->toContainKey('HOST');
            expect($req->getHeaders()['HOST'])->toBe(['example.com']);
        });

        it('assigns body, defaults to null', function() {
            expect((new Request('GET', '/'))->getBody())->toBeNull();
        });

        it('assigns body as resource if given is scalar', function() {
           expect((new Request('GET', '/', [], 'Hello World'))->getBody())->toBeA('resource');
        });

        it('throws InvalidArgumentException on invalid body', function() {
            expect(function() {
                new Request('GET', '/', [], []);
            })
            ->toThrow(new InvalidArgumentException);
        });
    });

    describe('->getTarget', function() {
        it('return forward slash when path component of the uri is not defined', function() {
            expect((new Request('GET', '?queryString'))->getTarget())->toBe('/?queryString');
        });

        it('return path and query string of the uri', function() {
            expect((new Request('GET', 'http://example.com/path?queryString'))->getTarget())->toBe('/path?queryString');
        });
    });

    describe('->getMediaType', function() {
        it('returns null when Content-Type is not defined', function() {
            expect((new Request('GET', '/'))->getMediaType())->toBeNull();
        });

        it('returns Content-Type header in lowercase', function() {
            expect((new Request('GET', '/', ['Content-Type' => ['TEXT/PLAIN']]))->getMediaType())->toBe('text/plain');
        });

        it('returns Content-Type header without charset', function() {
            expect((new Request('GET', '/', ['Content-Type' => ['text/plain;charset=ISO-8859-4']]))->getMediaType())->toBe('text/plain');
        });
    });

    describe('->getCharset', function() {
        it('returns null when Content-Type is not defined', function() {
            expect((new Request('GET', '/'))->getCharset())->toBeNull();
        });

        it('returns null when Content-Type charset is not given', function() {
            expect((new Request('GET', '/', ['Content-Type' => ['text/plain;important=yes']]))->getCharset())->toBeNull();
        });

        it('returns normalized Content-Type charset', function() {
            expect((new Request('GET', '/', ['Content-Type' => ['text/plain;CHARSET="UTF-8"']]))->getCharset())->toBe('utf-8');
        });
    });

    describe('->isForm', function() {
        it('returns true when Content-Type is application/x-www-form-urlencoded', function() {
            expect((new Request('GET', '/', ['Content-Type' => ['application/x-www-form-urlencoded']]))->isForm())->toBe(TRUE);
        });

        it('returns true when Content-Type is multipart/form-data', function() {
            expect((new Request('GET', '/', ['Content-Type' => ['multipart/form-data']]))->isForm())->toBe(TRUE);
        });

        it('returns false when Content-Type is text/plain', function() {
            expect((new Request('GET', '/', ['Content-Type' => ['text/plain']]))->isForm())->toBe(FALSE);
        });
    });
});