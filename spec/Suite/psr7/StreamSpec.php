<?php declare(strict_types=1);

use Psr\Http\Message\StreamInterface;
use ochenta\psr7\Stream;

describe('psr7\\Stream', function() {
    describe('->__toString', function() {
        it('returns an empty string if there is no resource', function() {
            expect((new Stream(NULL))->__toString())->toBe('');
        });
        it('returns content', function() {
            $resource = fopen('php://memory', 'w+');
            fwrite($resource, 'Hello');
            fseek($resource, 0);

            expect((new Stream($resource))->__toString())->toBe('Hello');
        });
    });
    describe('->__construct', function() {
        it('accepts an Stream and extracts it\'s resource', function() {
            expect((new Stream(new Stream('Hello')))->detach())->toBe('Hello');
        });
    });
    describe('->extend', function() {
        it('returns a new instance with the result of the callback', function() {
            $stream0 = new Stream('Hello');
            $stream1 = $stream0->extend(function(StreamInterface $stream2) use($stream0) {
                expect($stream2)->toBe($stream0);
                return 'World';
            });

            expect($stream0->detach())->toBe('Hello');
            expect($stream1->detach())->toBe('World');
        });
    });
    describe('->close', function() {
        it('unsets the resource if it isn\'t of type resource', function() {
            $stream = new Stream('Hello');
            $stream->close();

            expect($stream->detach())->toBeNull();
        });
        it('closes the pointer if there is resource', function() {
            $resource = fopen('php://memory', 'r');
            $stream = new Stream($resource);
            $stream->close();

            expect(function() use($resource) { fclose($resource); })->toThrow(new Kahlan\PhpErrorException);
        });
    });
    describe('->getSize', function() {
        it('returns null when there is no resource', function() {
            expect((new Stream(NULL))->getSize())->toBeNull();
        });
        it('returns size in bytes', function() {
            $resource = fopen('php://memory', 'w');
            fwrite($resource, 'Four');

            expect((new Stream($resource))->getSize())->toBe(4);
        });
    });
    describe('->tell', function() {
        it('throws RuntimeException when is there is no resource', function() {
            expect(function() {
                (new Stream(NULL))->tell();
            })
            ->toThrow(new RuntimeException);
        });
        it('throws RuntimeException when the resource is already closed', function() {
            expect(function() {
                $resource = fopen('php://memory', 'r');
                fclose($resource);
                (new Stream($resource))->tell();
            })
            ->toThrow(new RuntimeException);
        });
        it('returns pointer position', function() {
            $resource = fopen('php://memory', 'w');
            fwrite($resource, 'Four');

            expect((new Stream($resource))->tell())->toBe(4);
        });
    });
    describe('->eof', function() {
        it('returns true when there is no resource', function() {
            expect((new Stream(NULL))->eof())->toBe(TRUE);
        });
        it('returns true when the resource\'s pointer is at the end of the file', function() {
            $resource = fopen('php://memory', 'r');
            fwrite($resource, 'Four');
            fclose($resource);

            expect((new Stream($resource))->eof())->toBe(TRUE);
        });
        it('returns false when the resource\'s pointer is not at the end of the file', function() {
            expect((new Stream(fopen('php://memory', 'r')))->eof())->toBe(FALSE);
        });
    });
    describe('->isSeekable', function() {
        it('returns false when there is no resource', function() {
            expect((new Stream(NULL))->isSeekable())->toBe(FALSE);
        });
        it('returns bool for resources', function() {
            expect((new Stream(fopen('php://memory', 'a+')))->isSeekable())->toBe(TRUE);
        });
    });
    describe('->seek', function() {
        it('throws RuntimeException when is not seekable', function() {
            expect(function() { (new Stream(NULL))->seek(0); })->toThrow(new RuntimeException);
        });
        it('throws RuntimeException when offset is off boundaries', function() {
            expect(function() {
                $stream = new Stream(fopen('php://memory', 'r'));
                $stream->seek(1);
            })
            ->toThrow(new RuntimeException);
        });
        it('moves the pointer to the given offset', function() {
            $resource = fopen('php://memory', 'w');
            fwrite($resource, 'Four');

            expect(ftell($resource))->toBe(4);

            $stream = new Stream($resource);
            $stream->seek(2);

            expect(ftell($resource))->toBe(2);
        });
    });
    describe('->rewind', function() {
        it('moves the pointer to the begining', function() {
            $resource = fopen('php://memory', 'w');
            fwrite($resource, 'Four');

            expect(ftell($resource))->toBe(4);

            $stream = new Stream($resource);
            $stream->rewind();

            expect(ftell($resource))->toBe(0);
        });
    });
    describe('->isWritable', function() {
        it('returns false when there is no resource', function() {
            expect((new Stream(NULL))->isWritable())->toBe(FALSE);
        });
        it('returns true for writtable modes', function() {
            expect((new Stream(fopen('php://memory', 'w')))->isWritable())->toBe(TRUE);
            expect((new Stream(fopen('php://memory', 'a')))->isWritable())->toBe(TRUE);
            expect((new Stream(fopen('php://memory', 'r+')))->isWritable())->toBe(TRUE);
        });
        it('returns false for read-only mode', function() {
            expect((new Stream(fopen('php://memory', 'rb')))->isWritable())->toBe(FALSE);
        });
    });
    describe('->write', function() {
        it('throws RuntimeException when resource is not writable', function() {
            expect(function() {
                $stream = new Stream(fopen('php://memory', 'r'));
                $stream->write('Hello');
            })
            ->toThrow(new RuntimeException);
        });
        it('throws RuntimeException when the resource is already closed', function() {
            expect(function() {
                $resource = fopen('php://memory', 'r');
                fclose($resource);

                $stream = new Stream($resource);
                $stream->write('Hello');
            })
            ->toThrow(new RuntimeException);
        });
        it('returns written bytes', function() {
            expect((new Stream(fopen('php://memory', 'w')))->write('Hello'))->toBe(5);
        });
    });
    describe('->isReadable', function() {
        it('returns false when there is no resource', function() {
            expect((new Stream(NULL))->isReadable())->toBe(FALSE);
        });
        it('returns true for readable modes', function() {
            expect((new Stream(fopen('php://memory', 'r')))->isReadable())->toBe(TRUE);
            expect((new Stream(fopen('php://memory', 'a')))->isReadable())->toBe(TRUE);
            expect((new Stream(fopen('php://memory', 'w+')))->isReadable())->toBe(TRUE);
        });
    });
    describe('->read', function() {
        it('throws RuntimeException when resource is not readable', function() {
            expect(function() {
                $stream = new Stream(NULL);
                $stream->read(1);
            })
            ->toThrow(new RuntimeException);
        });
        it('throws RuntimeException when the resource is already closed', function() {
            expect(function() {
                $resource = fopen('php://memory', 'r');
                fclose($resource);

                $stream = new Stream($resource);
                $stream->read(1);
            })
            ->toThrow(new RuntimeException);
        });
        it('returns read data', function() {
            $resource = fopen('php://memory', 'w+');
            fwrite($resource, 'Hello');
            fseek($resource, 0);

            expect((new Stream($resource))->read(5))->toBe('Hello');
        });
    });
    describe('->getContents', function() {
        it('throws RuntimeException when resource is not readable', function() {
            expect(function() {
                $stream = new Stream(NULL);
                $stream->getContents();
            })
            ->toThrow(new RuntimeException);
        });
        it('throws RuntimeException when the resource is already closed', function() {
            expect(function() {
                $resource = fopen('php://memory', 'r');
                fclose($resource);

                $stream = new Stream($resource);
                $stream->getContents();
            })
            ->toThrow(new RuntimeException);
        });
        it('returns content', function() {
            $resource = fopen('php://memory', 'w+');
            fwrite($resource, 'Hello');
            fseek($resource, 0);

            expect((new Stream($resource))->getContents())->toBe('Hello');
        });
    });
    describe('->getMetadata', function() {
        it('returns null if when there is no resource', function() {
            expect((new Stream(NULL))->getMetadata())->toBeNull();
            expect((new Stream(NULL))->getMetadata('anykey'))->toBeNull();
        });
        it('returns all metadata when no key given', function() {
            $stream = new Stream(fopen('php://memory', 'r'));

            expect($stream->getMetadata())->toBeA('array')->toContainKey('uri');
            expect($stream->getMetadata()['uri'])->toBe('php://memory');
        });
        it('returns value for given key', function() {
            expect((new Stream(fopen('php://memory', 'r')))->getMetadata('uri'))->toBe('php://memory');
        });
    });
});