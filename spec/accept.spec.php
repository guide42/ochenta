<?php declare(strict_types=1);

use ochenta\{Request, accept};

describe('accept', function() {
    describe('mediatypes', function() {
        it('returns empty list when available list is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/json',
            ]);
            expect(accept\mediatypes($req, []))->toBe([]);
        });

        it('returns all available when accept is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
            ]);
            $available = ['application/json'];
            expect(accept\mediatypes($req, $available))->toEqual($available);
        });

        it('throws UnexpectedValueException when available content type has no separator', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/json',
            ]);
            expect(function() use($req) {
                accept\mediatypes($req, ['json']);
            })->toThrow(new UnexpectedValueException);
        });

        it('throws UnexpectedValueException when accept media type has no separator', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'json',
            ]);
            expect(function() use($req) {
                accept\mediatypes($req, ['application/json']);
            })->toThrow(new UnexpectedValueException);
        });

        it('returns matched when accept base type is asterisk and sub type equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => '*/json',
            ]);
            $mediaTypes = accept\mediatypes($req, ['application/json']);
            expect(array_shift($mediaTypes))->toEqual('application/json');
        });

        it('returns matched when accept sub type is asterisk and base type equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/*',
            ]);
            $mediaTypes = accept\mediatypes($req, ['application/json']);
            expect(array_shift($mediaTypes))->toEqual('application/json');
        });

        it('returns matched when type equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/xml, application/json',
            ]);
            $mediaTypes = accept\mediatypes($req, ['application/json']);
            expect(array_shift($mediaTypes))->toEqual('application/json');
        });

        it('returns matched when accept vendor sub type is asterisk', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'aplication/*+json',
            ]);
            $mediaTypes = accept\mediatypes($req, ['application/vnd.example+json']);
            expect(array_shift($mediaTypes))->toEqual('application/vnd.example+json');
        });

        it('returns matched when accept format sub type is asterisk', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'aplication/vnd.example+*',
            ]);
            $mediaTypes = accept\mediatypes($req, ['application/vnd.example+json']);
            expect(array_shift($mediaTypes))->toEqual('application/vnd.example+json');
        });

        it('returns matched when type equals with plus', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/vnd.example+xml, application/vnd.example+json',
            ]);
            $mediaTypes = accept\mediatypes($req, ['application/vnd.example+json']);
            expect(array_shift($mediaTypes))->toEqual('application/vnd.example+json');
        });

        it('returns empty list when none matched', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/xml, application/json',
            ]);
            expect(accept\mediatypes($req, ['application/zip']))->toBe([]);
        });
    });

    describe('charsets', function() {
        it('returns empty list when available list is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-charset' => 'utf-8',
            ]);
            expect(accept\charsets($req, []))->toBe([]);
        });

        it('returns all available when accept is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
            ]);
            $available = ['utf-8'];
            expect(accept\charsets($req, $available))->toEqual($available);
        });

        it('returns matched accept charset is asterisk', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-charset' => '*',
            ]);
            $charsets = accept\charsets($req, ['utf-8']);
            expect(array_shift($charsets))->toEqual('utf-8');
        });

        it('returns matched charsets equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-charset' => 'utf-8',
            ]);
            $charsets = accept\charsets($req, ['utf-8']);
            expect(array_shift($charsets))->toEqual('utf-8');
        });

        it('returns NULL when none matched', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-charset' => 'UTF-8, ISO-8859-1',
            ]);
            $charsets = accept\charsets($req, ['US-ASCII']);
            expect(array_shift($charsets))->toBe(NULL);
        });
    });

    describe('encodings', function() {
        it('returns empty list when available list is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-encoding' => 'compress',
            ]);
            expect(accept\encodings($req, []))->toBe([]);
        });

        it('returns all available when accept is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
            ]);
            $available = ['compress'];
            expect(accept\encodings($req, $available))->toEqual($available);
        });

        it('returns matched accept charset is asterisk', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-encoding' => '*',
            ]);
            $encodings = accept\encodings($req, ['compress']);
            expect(array_shift($encodings))->toEqual('compress');
        });

        it('returns matched charsets equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-encoding' => 'compress',
            ]);
            $encodings = accept\encodings($req, ['compress']);
            expect(array_shift($encodings))->toEqual('compress');
        });

        it('returns empty list when none matched', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-encoding' => 'compress, gzip',
            ]);
            $encodings = accept\encodings($req, ['deflate']);
            expect($encodings)->toBe([]);
        });
    });

    describe('languages', function() {
        it('returns empty list when available list is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => 'en',
            ]);
            expect(accept\languages($req, []))->toBe([]);
        });

        it('returns all available when accept is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
            ]);
            $available = ['en'];
            expect(accept\languages($req, $available))->toEqual($available);
        });

        it('returns matched when accept base lang is asterisk and sub lang equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => '*-US',
            ]);
            $languages = accept\languages($req, ['en-US']);
            expect(array_shift($languages))->toEqual('en-US');
        });

        it('returns matched when accept sub lang is asterisk and base lang equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => 'en-*',
            ]);
            $languages = accept\languages($req, ['en-US']);
            expect(array_shift($languages))->toEqual('en-US');
        });

        it('returns matched when lang equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => 'en, en-US',
            ]);
            $languages = accept\languages($req, ['en']);
            expect(array_shift($languages))->toEqual('en');
        });

        it('returns matched when lang equals with sub lang', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => 'en, en-US',
            ]);
            $languages = accept\languages($req, ['en-US']);
            expect(array_shift($languages))->toEqual('en-US');
        });

        it('returns empty list when none matched', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => 'en, en-US',
            ]);
            expect(accept\languages($req, ['es-AR']))->toBe([]);
        });
    });
});