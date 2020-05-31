<?php declare(strict_types=1);

use ochenta\{Accept, Request};

describe('Accept', function() {
    context('->__construct', function() {
        it('accepts Request object', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
            ]);
            expect(function() use($req) {
                $accept = new Accept($req);

                expect($accept)->toBeAnInstanceOf(Accept::class);
                expect($accept->getRequest())->toBe($req);
            })->not->toThrow();
        });
    });

    context('->getRequest', function() {
        it('returns the Request object given to the constructor', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
            ]);
            expect((new Accept($req))->getRequest())->toBe($req);
        });
    });

    context('->mediatype', function() {
        it('returns NULL when available list is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/json',
            ]);
            $mediaType = (new Accept($req))->mediatype([]);
            expect($mediaType)->toBe(NULL);
        });

        it('returns first available when accept is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
            ]);
            $mediaType = (new Accept($req))->mediatype(['application/json']);
            expect($mediaType)->toEqual('application/json');
        });

        it('throws UnexpectedValueException when available content type has no separator', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/json',
            ]);
            $accept = new Accept($req);
            expect(function() use($accept) {
                $accept->mediatype(['json']);
            })->toThrow(new UnexpectedValueException);
        });

        it('throws UnexpectedValueException when accept media type has no separator', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'json',
            ]);
            $accept = new Accept($req);
            expect(function() use($accept) {
                $accept->mediatype(['application/json']);
            })->toThrow(new UnexpectedValueException);
        });

        it('returns matched when accept base type is asterisk and sub type equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => '*/json',
            ]);
            $mediaType = (new Accept($req))->mediatype(['application/json']);
            expect($mediaType)->toEqual('application/json');
        });

        it('returns matched when accept sub type is asterisk and base type equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/*',
            ]);
            $mediaType = (new Accept($req))->mediatype(['application/json']);
            expect($mediaType)->toEqual('application/json');
        });

        it('returns matched when type equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/xml, application/json',
            ]);
            $mediaType = (new Accept($req))->mediatype(['application/json']);
            expect($mediaType)->toEqual('application/json');
        });

        it('returns matched when accept vendor sub type is asterisk', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'aplication/*+json',
            ]);
            $mediaType = (new Accept($req))->mediatype(['application/vnd.example+json']);
            expect($mediaType)->toEqual('application/vnd.example+json');
        });

        it('returns matched when accept format sub type is asterisk', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'aplication/vnd.example+*',
            ]);
            $mediaType = (new Accept($req))->mediatype(['application/vnd.example+json']);
            expect($mediaType)->toEqual('application/vnd.example+json');
        });

        it('returns matched when type equals with plus', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/vnd.example+xml, application/vnd.example+json',
            ]);
            $mediaType = (new Accept($req))->mediatype(['application/vnd.example+json']);
            expect($mediaType)->toEqual('application/vnd.example+json');
        });

        it('returns NULL when none matched', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept' => 'application/xml, application/json',
            ]);
            $mediaType = (new Accept($req))->mediatype(['application/zip']);
            expect($mediaType)->toBe(NULL);
        });
    });

    context('->charset', function() {
        it('returns NULL when available list is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-charset' => 'utf-8',
            ]);
            $charset = (new Accept($req))->charset([]);
            expect($charset)->toBe(NULL);
        });

        it('returns first available when accept is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
            ]);
            $charset = (new Accept($req))->charset(['utf-8']);
            expect($charset)->toEqual('utf-8');
        });

        it('returns matched accept charset is asterisk', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-charset' => '*',
            ]);
            $charset = (new Accept($req))->charset(['utf-8']);
            expect($charset)->toEqual('utf-8');
        });

        it('returns matched charsets equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-charset' => 'utf-8',
            ]);
            $charset = (new Accept($req))->charset(['utf-8']);
            expect($charset)->toEqual('utf-8');
        });

        it('returns NULL when none matched', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-charset' => 'UTF-8, ISO-8859-1',
            ]);
            $charset = (new Accept($req))->charset(['US-ASCII']);
            expect($charset)->toBe(NULL);
        });
    });

    context('->encoding', function() {
        it('returns NULL when available list is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-encoding' => 'compress',
            ]);
            $encoding = (new Accept($req))->encoding([]);
            expect($encoding)->toBe(NULL);
        });

        it('returns first available when accept is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
            ]);
            $encoding = (new Accept($req))->encoding(['compress']);
            expect($encoding)->toEqual('compress');
        });

        it('returns matched accept charset is asterisk', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-encoding' => '*',
            ]);
            $encoding = (new Accept($req))->encoding(['compress']);
            expect($encoding)->toEqual('compress');
        });

        it('returns matched charsets equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-encoding' => 'compress',
            ]);
            $encoding = (new Accept($req))->encoding(['compress']);
            expect($encoding)->toEqual('compress');
        });

        it('returns NULL when none matched', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-encoding' => 'compress, gzip',
            ]);
            $encoding = (new Accept($req))->encoding(['deflate']);
            expect($encoding)->toBe(NULL);
        });
    });

    context('->language', function() {
        it('returns NULL when available list is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => 'en',
            ]);
            $language = (new Accept($req))->language([]);
            expect($language)->toBe(NULL);
        });

        it('returns first available when accept is empty', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
            ]);
            $language = (new Accept($req))->language(['en']);
            expect($language)->toEqual('en');
        });

        it('returns matched when accept base lang is asterisk and sub lang equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => '*-US',
            ]);
            $language = (new Accept($req))->language(['en-US']);
            expect($language)->toEqual('en-US');
        });

        it('returns matched when accept sub lang is asterisk and base lang equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => 'en-*',
            ]);
            $language = (new Accept($req))->language(['en-US']);
            expect($language)->toEqual('en-US');
        });

        it('returns matched when lang equals', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => 'en, en-US',
            ]);
            $language = (new Accept($req))->language(['en']);
            expect($language)->toEqual('en');
        });

        it('returns matched when lang equals with sub lang', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => 'en, en-US',
            ]);
            $language = (new Accept($req))->language(['en-US']);
            expect($language)->toEqual('en-US');
        });

        it('returns NULL when none matched', function() {
            $req = new Request('GET', '/', [
                'host' => 'example.com',
                'accept-language' => 'en, en-US',
            ]);
            $language = (new Accept($req))->language(['es-AR']);
            expect($language)->toBe(NULL);
        });
    });
});