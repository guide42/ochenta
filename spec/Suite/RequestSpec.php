<?php

use Ochenta\Request;

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
    });

    describe('->getTarget', function() {
        it('return forward slash when path component of the uri is not defined', function() {
            expect((new Request('GET', '?queryString'))->getTarget())->toBe('/');
        });

        it('return path of the uri', function() {
            expect((new Request('GET', 'http://example.com/path?queryString'))->getTarget())->toBe('/path');
        });
    });
});