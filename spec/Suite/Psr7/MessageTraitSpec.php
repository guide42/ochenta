<?php

use Kahlan\Plugin\Stub;
use Psr\Http\Message\StreamInterface;
use Ochenta\Psr7\MessageTrait;
use Ochenta\Psr7\Stream;

describe('Psr7\\MessageTrait', function() {
    describe('->getProtocolVersion', function() {
        it('returns 1.1', function() {
            expect((Stub::create(['uses' => MessageTrait::class]))->getProtocolVersion())->toBe('1.1');
        });
    });
    describe('->withProtocolVersion', function() {
        it('throws BadMethodCallException because is not supported', function() {
            expect(function() {
                $message = Stub::create(['uses' => MessageTrait::class]);
                $message->withProtocolVersion('2.0');
            })
            ->toThrow(new BadMethodCallException);
        });
    });
    describe('->getHeaders', function() {
        it('returns headers with keys in the original name', function() {
            $message = Stub::create(['uses' => MessageTrait::class]);
            $message = $message->withHeader('Content-Type', 'text/plain');

            expect($message->getHeaders())->toBe(['Content-Type' => ['text/plain']]);
        });
    });
    describe('->hasHeader', function() {
        it('returns true when given normalized name is found', function() {
            $message = Stub::create(['uses' => MessageTrait::class]);
            $message = $message->withHeader('Content-Type', 'text/plain');

            expect($message->hasHeader('CONTENT-TYPE'))->toBe(TRUE);
            expect($message->hasHeader('CoNtEnT-tYpE'))->toBe(TRUE);
            expect($message->hasHeader('content-type'))->toBe(TRUE);
        });
        it('returns true if the header exists, false when not', function() {
            $message = Stub::create(['uses' => MessageTrait::class]);
            $message = $message->withHeader('Content-Type', 'text/plain');

            expect($message->hasHeader('Content-Type'))->toBe(TRUE);
            expect($message->hasHeader('Content-Length'))->toBe(false);
        });
        it('returns false when is defined by empty', function() {
            $message = Stub::create(['uses' => MessageTrait::class]);
            $message = $message->withHeader('Content-Type', NULL);

            expect($message->hasHeader('Content-Type'))->toBe(false);
        });
    });
    describe('->getHeader', function() {
        it('returns the list of values for given normalized name', function() {
            $message = Stub::create(['uses' => MessageTrait::class]);
            $message = $message->withHeader('Content-Language', 'en');
            $message = $message->withAddedHeader('Content-Language', 'es');

            expect($message->getHeader('Content-Language'))->toBe(['es', 'en']);
        });
        it('returns an empty list when header is not found', function() {
            expect(Stub::create(['uses' => MessageTrait::class])->getHeader('Date'))->toBe([]);
        });
    });
    describe('->getHeaderLine', function() {
        it('returns comma separated values of the given normalized header', function() {
            $message = Stub::create(['uses' => MessageTrait::class]);
            $message = $message->withAddedHeader('Content-Language', 'en');
            $message = $message->withAddedHeader('Content-Language', 'es');

            expect($message->getHeaderLine('Content-Language'))->toBe('es, en');
        });
    });
    describe('->withHeader', function() {
        it('returns a copy that contains a new header', function() {
            $msg0 = Stub::create(['uses' => MessageTrait::class]);
            $msg1 = $msg0->withHeader('Content-Type', 'text/plain');

            expect($msg0->hasHeader('Content-Type'))->toBe(false);
            expect($msg1->hasHeader('Content-Type'))->toBe(TRUE);
        });
        it('returns a copy that replace existing header', function() {
            $msg0 = Stub::create(['uses' => MessageTrait::class]);
            $msg0 = $msg0->withHeader('Content-Type', 'text/plain');
            $msg1 = $msg0->withHeader('Content-Type', 'text/html');

            expect($msg0->getHeader('Content-Type'))->toBe(['text/plain']);
            expect($msg1->getHeader('Content-Type'))->toBe(['text/html']);
        });
    });
    describe('->withAddedHeader', function() {
        it('returns a copy that contains a new header', function() {
            $msg0 = Stub::create(['uses' => MessageTrait::class]);
            $msg1 = $msg0->withAddedHeader('Content-Type', 'text/plain');

            expect($msg0->hasHeader('Content-Type'))->toBe(false);
            expect($msg1->hasHeader('Content-Type'))->toBe(TRUE);
        });
        it('returns a copy that add a value to existing header', function() {
            $msg0 = Stub::create(['uses' => MessageTrait::class]);
            $msg0 = $msg0->withAddedHeader('Content-Type', 'text/plain');
            $msg1 = $msg0->withAddedHeader('Content-Type', 'text/html');

            expect($msg0->getHeader('Content-Type'))->toBe(['text/plain']);
            expect($msg1->getHeader('Content-Type'))->toBe(['text/html', 'text/plain']);
        });
    });
    describe('->withoutHeader', function() {
        it('returns a copy that doesn\'t contains the normalized header', function() {
            $msg0 = Stub::create(['uses' => MessageTrait::class]);
            $msg0 = $msg0->withAddedHeader('Content-Type', 'text/plain');
            $msg1 = $msg0->withoutHeader('CONTENT-TYPE', 'text/plain');

            expect($msg0->hasHeader('Content-Type'))->toBe(TRUE);
            expect($msg1->hasHeader('Content-Type'))->toBe(false);
        });
    });
    describe('->getBody', function() {
        it('returns an instance of StreamInterface when empty', function() {
            $message = Stub::create(['uses' => MessageTrait::class]);

            expect($message->getBody())->toBeAnInstanceOf(StreamInterface::class);
            expect($message->getBody()->extract())->toBeNull();
        });
        it('returns stream given in ->withBody', function() {
            $stream = new Stream(NULL);

            $message = Stub::create(['uses' => MessageTrait::class]);
            $message = $message->withBody($stream);

            expect($message->getBody())->toBe($stream);
        });
    });
    describe('->withBody', function() {
        it('returns a copy with the given stream', function() {
            $stream = new Stream(NULL);

            $msg0 = Stub::create(['uses' => MessageTrait::class]);
            $msg1 = $msg0->withBody($stream);

            expect($msg0->getBody())->not->toBe($stream);
            expect($msg1->getBody())->toBe($stream);
        });
        it('returns same instance when body already assigned', function() {
            $stream = new Stream(NULL);

            $msg0 = Stub::create(['uses' => MessageTrait::class]);
            $msg0 = $msg0->withBody($stream);
            $msg1 = $msg0->withBody($stream);

            expect($msg0)->toBe($msg1);
        });
    });
});