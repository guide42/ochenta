<?php

use Ochenta\Response;

describe('Response', function() {
    describe('->__construct', function() {
        it('throws an InvalidArgumentException when status code isn\'t between 100 and 600', function() {
            expect(function() {
                new Response(42);
            })
            ->toThrow(new InvalidArgumentException);
        });
    });
});