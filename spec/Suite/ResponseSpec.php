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
    });
});