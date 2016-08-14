<?php

use ochenta\psr7\Response;

describe('psr7\\Response', function() {
    describe('->getStatusCode', function() {
        it('returns given status code', function() {
            expect((new Response(200))->getStatusCode())->toBe(200);
        });
    });
    describe('->withStatus', function() {
        it('returns a new instance with given status code', function() {
            $res0 = new Response(200);
            $res1 = $res0->withStatus(204);

            expect($res0->getStatusCode())->toBe(200);
            expect($res1->getStatusCode())->toBe(204);
        });
        it('returns a new instance with given phrase', function() {
            $res0 = new Response(200);
            $res1 = $res0->withStatus(204, 'Hasta la vista body');

            expect($res0->getReasonPhrase())->toBe('OK');
            expect($res1->getReasonPhrase())->toBe('Hasta la vista body');
        });
    });
    describe('->getReasonPhrase', function() {
        it('returns default phrase by default', function() {
            expect((new Response(200))->getReasonPhrase())->toBe('OK');
        });
        it('returns given phrase', function() {
            $res = new Response(200);
            $res = $res->withStatus(204, 'Hasta la vista body');

            expect($res->getReasonPhrase())->toBe('Hasta la vista body');
        });
        it('returns empty string if default phrase not found', function() {
            expect((new Response(299))->getReasonPhrase())->toBe('');
        });
    });
});