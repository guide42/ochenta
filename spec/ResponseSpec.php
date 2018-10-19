<?php declare(strict_types=1);

use ochenta\{Request, Response};

describe('Response', function() {
    describe('->__construct', function() {
        it('throws InvalidArgumentException when status code isn\'t between 100 and 600', function() {
            expect(function() {
                new Response(42);
            })
            ->toThrow(new InvalidArgumentException);
        });

        it('throws InvalidArgumentException when header is not valid', function() {
            expect(function() {
                new Response(200, ['Host' => new stdClass]);
            })
            ->toThrow(new InvalidArgumentException);
        });

        it('assigns headers defaults', function() {
            expect((new Response(200, []))->getHeaders())->toBe([
                'CACHE-CONTROL' => ['no-store', 'no-cache', 'must-revalidate',
                                    'post-check=0', 'pre-check=0'],
                'CONTENT-TYPE'  => ['text/html; charset=utf-8'],
            ]);
        });

        it('assigns headers, defaults can be overriden', function() {
            expect((new Response(200, ['Cache-Control' => 'no-cache']))->getHeaders())->toBe([
                'CACHE-CONTROL' => ['no-cache'],
                'CONTENT-TYPE'  => ['text/html; charset=utf-8'],
            ]);
        });

        it('assigns headers as array if scalar value given', function() {
            expect((new Response(200, ['Host' => 'example.com']))->getHeaders()['HOST'])->toBe(['example.com']);
        });

        it('removes content for empty responses status codes', function() {
            $req = new Response(204, ['Content-Type' => 'text/plain'], 'Hello World');

            expect($req->getHeaders())->not->toContainKey('CONTENT-TYPE');
            expect($req->getBody())->toBeNull();
        });

        it('assigns content type, defaults to text/html', function() {
            expect((new Response(200, []))->getHeaders()['CONTENT-TYPE'])->toBe(['text/html; charset=utf-8']);
        });

        it('assigns content type charset, defaults to utf-8', function() {
           expect((new Response(200, ['Content-Type' => 'text/html']))->getHeaders()['CONTENT-TYPE'])->toBe(['text/html; charset=utf-8']);
        });

        it('throws InvalidArgumentException on invalid body', function() {
            expect(function() {
                new Response(200, [], []);
            })
            ->toThrow(new InvalidArgumentException);
        });
    });

    describe('->prepare', function() {
        it('returns a clone of the response', function() {
            $old = new Response(200);
            $new = $old->prepare(new Request('GET', '/', ['Host' => 'example.com']));

            expect($old)->not->toBe($new);
            expect($new)->toBeAnInstanceOf(Response::class);
        });

        it('removes body if request is HEAD', function() {
            $res = new Response(200, [], 'Hello World');
            $req = new Request('HEAD', '/', ['Host' => 'example.com']);

            expect($res->prepare($req)->getBody())->toBeNull();
        });
    });

    describe('->isRedirect', function() {
        it('returns true when status code is 302', function() {
            expect((new Response(302))->isRedirect())->toBe(TRUE);
        });
    });
});