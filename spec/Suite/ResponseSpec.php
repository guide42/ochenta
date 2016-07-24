<?php

use Ochenta\Response;

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

        it('assigns headers as array if scalar value given', function() {
            expect((new Response(200, ['Host' => 'example.com']))->getHeaders()['HOST'])->toBe(['example.com']);
        });

        it('removes content for empty responses status codes', function() {
            $req = new Response(204, ['Content-Type' => 'text/plain'], 'Hello World');

            expect($req->getHeaders())->not->toContainKey('CONTENT-TYPE');
            expect($req->getBody())->toBeNull();
        });

        it('assigns content type, defaults to text/html', function() {
            expect((new Response(200, []))->getHeaders()['CONTENT-TYPE'])->toBe(['text/html; charset=UTF-8']);
        });

        it('assigns content type charset, defaults to utf-8', function() {
           expect((new Response(200, ['Content-Type' => 'text/html']))->getHeaders()['CONTENT-TYPE'])->toBe(['text/html; charset=UTF-8']);
        });
    });
});