<?php

use Ochenta\Psr7\Request;
use Ochenta\Psr7\Stream;
use Ochenta\Psr7\Uri;

describe('Psr7\\Request', function() {
    describe('->getHeaders', function() {
        it('returns headers with keys in the original name', function() {
            $message = new Request('GET', '/', ['Content-Type' => ['text/plain']]);
            $message = $message->withAddedHeader('Content-Type', 'text/html');

            expect($message->getHeaders())->toBe(['Content-Type' => ['text/html', 'text/plain']]);
        });
    });
    describe('->getRequestTarget', function() {
        it('returns the same value as ->getTarget', function() {
            $req = new Request('GET', '/');
            expect($req->getRequestTarget())->toBe($req->getTarget());
        });
    });
    describe('->withRequestTarget', function() {
        it('returns a copy with a new request target', function() {
            $req = new Request('GET', '/');
            $req = $req->withRequestTarget('/path');

            expect($req->getRequestTarget())->toBe('/path');
        });
    });
    describe('->withMethod', function() {
        it('returns a copy with new method', function() {
            $req = new Request('GET', '/');
            $req = $req->withMethod('PoSt');

            expect($req->getMethod())->toBe('POST');
        });
    });
    describe('->getUri', function() {
        it('returns an instance of Psr7\\Uri', function() {
            $req = new Request('GET', '/');
            $uri = $req->getUri();

            expect($uri)->toBeAnInstanceOf(Uri::class);
            expect($uri->extract())->toBe(['path' => '/']);
        });
    });
    describe('->withUri', function() {
        it('return a new instance with given uri', function() {
            $req0 = new Request('GET', '/');
            $req1 = $req0->withUri(new Uri(['path' => '/path']));

            expect($req0->getUri()->getPath())->toBe('/');
            expect($req1->getUri()->getPath())->toBe('/path');
        });
        it('return a new intance with HOST header from given uri', function() {
            $req = new Request('GET', '/');
            $req = $req->withUri(new Uri('http://ochenta/'));

            expect($req->hasHeader('Host'))->toBe(true);
            expect($req->getHeader('Host'))->toBe(['ochenta']);
        });
        it('return a new intance without HOST header when $preserveHost=true', function() {
            $req0 = new Request('GET', '/');
            $req1 = $req0->withUri(new Uri('http://ochenta/'), true);

            expect($req0->hasHeader('Host'))->toBe(false);
            expect($req1->hasHeader('Host'))->toBe(false);
            expect($req1->getHeader('Host'))->toBe([]);
        });
    });
});