<?php

use Ochenta\Psr7\ServerRequest;
use Ochenta\Psr7\UploadedFile;

describe('Psr7\\ServerRequest', function() {
    describe('->getRequestTarget', function() {
        it('returns the same value as ->getTarget', function() {
            $req = new ServerRequest;
            expect($req->getRequestTarget())->toBe($req->getTarget());
        });
        it('returns given request target', function() {
            $req = new ServerRequest;
            $req = $req->withRequestTarget('/path');

            expect($req->getRequestTarget())->toBe('/path');
        });
    });
    describe('->getServerParams', function() {
        it('returns the given server parameters', function() {
            expect((new ServerRequest(['HTTP_HOST' => 'ochenta']))->getServerParams())->toBe(['HTTP_HOST' => 'ochenta']);
        });
    });
    describe('->getCookieParams', function() {
        it('returns the given cookie parameters', function() {
            expect((new ServerRequest(null, null, null, null, ['foo' => 'bar']))->getCookieParams())->toBe(['foo' => 'bar']);
        });
    });
    describe('->withCookieParams', function() {
        it('returns a copy with the given cookie parameters', function() {
            $req0 = new ServerRequest(null, null, null, null, ['foo' => 'bar']);
            $req1 = $req0->withCookieParams(['bar' => 'foo']);

            expect($req0->getCookieParams())->toBe(['foo' => 'bar']);
            expect($req1->getCookieParams())->toBe(['bar' => 'foo']);
        });
    });
    describe('->getQueryParams', function() {
        it('returns the given query parameters', function() {
            expect((new ServerRequest(null, ['foo' => 'bar']))->getQueryParams())->toBe(['foo' => 'bar']);
        });
    });
    describe('->withQueryParams', function() {
        it('returns a copy with the given query parameters', function() {
            $req0 = new ServerRequest(null, ['foo' => 'bar']);
            $req1 = $req0->withQueryParams(['bar' => 'foo']);

            expect($req0->getQueryParams())->toBe(['foo' => 'bar']);
            expect($req1->getQueryParams())->toBe(['bar' => 'foo']);
        });
    });
    describe('->getUploadedFiles', function() {
        it('returns the given query parameters', function() {
            $req = new ServerRequest(null, null, null, [
                'avatar' => array(
                    'tmp_name' => '/tmp/phpUxcOty',
                    'name'     => 'avatar.png',
                    'type'     => 'image/png',
                    'size'     => 73096,
                    'error'    => 0,
                ),
            ]);

            expect($req->getUploadedFiles())->toBeA('array')->toHaveLength(1);
            expect(current($req->getUploadedFiles()))->toBeAnInstanceOf(UploadedFile::class);
            expect(current($req->getUploadedFiles())->getClientFilename())->toBe('avatar.png');
        });
    });
    describe('->withUploadedFiles', function() {
        it('returns a copy with the given uploaded files', function() {
            $req0 = new ServerRequest(null, null, null, []);
            $req1 = $req0->withUploadedFiles([
                'avatar' => new UploadedFile('/tmp/phpUxcOty', 73096, 0, 'avatar.png', 'image/png'),
            ]);

            expect($req0->getUploadedFiles())->toBe([]);
            expect($req1->getUploadedFiles())->toBeA('array')->toContainKey('avatar');
        });
    });
    describe('->withParsedBody', function() {
        it('returns a copy with the given query parameters', function() {
            $req0 = new ServerRequest(null, null, ['foo' => 'bar']);
            $req1 = $req0->withParsedBody(['bar' => 'foo']);

            expect($req0->getParsedBody())->toBe(['foo' => 'bar']);
            expect($req1->getParsedBody())->toBe(['bar' => 'foo']);
        });
    });
    describe('->getAttributes', function() {
        it('returns attributes, default empty list', function() {
            expect((new ServerRequest)->getAttributes())->toBe([]);
        });
    });
    describe('->getAttribute', function() {
        it('returns one attribute, default null', function() {
            expect((new ServerRequest)->getAttribute('foo'))->toBeNull();
        });
        it('returns given by default if attribte doesn\'t exists', function() {
            expect((new ServerRequest)->getAttribute('foo', 'bar'))->toBe('bar');
        });
        it('returns given attribute value', function() {
            $req = new ServerRequest;
            $req = $req->withAttribute('foo', 'bar');

            expect($req->getAttribute('foo'))->toBe('bar');
        });
    });
    describe('->withAttribute', function() {
        it('returns a copy with the given attribute set', function() {
            $req0 = new ServerRequest;
            $req1 = $req0->withAttribute('foo', 'bar');

            expect($req0->getAttributes())->toBe([]);
            expect($req1->getAttributes())->toBe(['foo' => 'bar']);
        });
        it('returns a copy with the given attribute overriden', function() {
            $req0 = new ServerRequest;
            $req1 = $req0->withAttribute('foo', 'bar');
            $req2 = $req1->withAttribute('foo', 'baz');

            expect($req2->getAttributes())->toBe(['foo' => 'baz']);
        });
    });
    describe('->withoutAttribute', function() {
        it('returns a copy without the given attribute', function() {
            $req0 = new ServerRequest;
            $req1 = $req0->withAttribute('foo', 'bar');
            $req2 = $req1->withoutAttribute('foo');

            expect($req2->getAttributes())->toBe([]);
        });
    });
});