<?php

use Psr\Http\Message\StreamInterface;
use Ochenta\Psr7\UploadedFile;

describe('Psr7\\UploadedFile', function() {
    describe('->getStream', function() {
        it('throws RuntimeException when error is not UPLOAD_ERR_OK', function() {
            expect(function() {
                (new UploadedFile('', 0, UPLOAD_ERR_NO_FILE))->getStream();
            })
            ->toThrow(new RuntimeException);
        });
        it('returns an instance of StreamInterface', function() {
            $tmpfile = tempnam(sys_get_temp_dir(), 'kahlan/');
            skipIf($tmpfile === false);
            touch($tmpfile);
            expect((new UploadedFile($tmpfile, 0, UPLOAD_ERR_OK))->getStream())->toBeAnInstanceOf(StreamInterface::class);
            unlink($tmpfile);
        });
    });
    describe('->moveTo', function() {
        it('throws InvalidArgumentException when target path is empty', function() {
            expect(function() {
                (new UploadedFile('', 0, UPLOAD_ERR_NO_FILE))->moveTo('');
            })
            ->toThrow(new InvalidArgumentException);
        });
        it('throws RuntimeException when error is not UPLOAD_ERR_OK', function() {
            expect(function() {
                (new UploadedFile('', 0, UPLOAD_ERR_NO_FILE))->moveTo('/tmp/kahlan/ochentatmp');
            })
            ->toThrow(new RuntimeException);
        });
        it('renames the given file', function() {
            $tmpfile = tempnam(sys_get_temp_dir(), 'kahlan/');
            skipIf($tmpfile === false);
            touch($tmpfile);
            (new UploadedFile($tmpfile, 0, UPLOAD_ERR_OK))->moveTo('/tmp/kahlan/ochentatmp');
            expect(file_exists($tmpfile))->toBe(false);
            expect(file_exists('/tmp/kahlan/ochentatmp'))->toBe(true);
            unlink('/tmp/kahlan/ochentatmp');
        });
    });
    describe('->getError', function() {
        it('returns the given error', function() {
            expect((new UploadedFile('', 0, UPLOAD_ERR_NO_FILE))->getError())->toBe(UPLOAD_ERR_NO_FILE);
        });
    });
    describe('->getClientFilename', function() {
        it('returns the save value as ->getClientName', function() {
            $file = new UploadedFile('', 0, UPLOAD_ERR_NO_FILE, 'README.txt');
            expect($file->getClientFilename())->toBe($file->getClientName());
        });
    });
    describe('->getClientMediaType', function() {
        it('returns the save value as ->getClientType', function() {
            $file = new UploadedFile('', 0, UPLOAD_ERR_NO_FILE, null, 'text/plain');
            expect($file->getClientMediaType())->toBe($file->getClientType());
        });
    });
});