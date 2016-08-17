<?php declare(strict_types=1);

use Psr\Http\Message\StreamInterface;
use ochenta\psr7\UploadedFile;

describe('psr7\\UploadedFile', function() {
    describe('->getStream', function() {
        it('throws RuntimeException when error is not UPLOAD_ERR_OK', function() {
            expect(function() {
                (new UploadedFile('', 0, UPLOAD_ERR_NO_FILE))->getStream();
            })
            ->toThrow(new RuntimeException);
        });
        it('returns an instance of StreamInterface', function() {
            $tmpfile = tempnam(sys_get_temp_dir(), 'kahlan/');
            skipIf($tmpfile === FALSE);
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
            skipIf($tmpfile === FALSE);
            touch($tmpfile);
            (new UploadedFile($tmpfile, 0, UPLOAD_ERR_OK))->moveTo('/tmp/kahlan/ochentatmp');
            expect(file_exists($tmpfile))->toBe(FALSE);
            expect(file_exists('/tmp/kahlan/ochentatmp'))->toBe(TRUE);
            unlink('/tmp/kahlan/ochentatmp');
        });
    });
    describe('->getSize', function() {
        it('returns the given size', function() {
            expect((new UploadedFile('', 0, UPLOAD_ERR_NO_FILE))->getSize())->toBe(0);
        });
    });
    describe('->getError', function() {
        it('returns the given error', function() {
            expect((new UploadedFile('', 0, UPLOAD_ERR_NO_FILE))->getError())->toBe(UPLOAD_ERR_NO_FILE);
        });
    });
    describe('->getClientFilename', function() {
        it('returns the client name', function() {
            $file = new UploadedFile('', 0, UPLOAD_ERR_NO_FILE, 'README.txt');
            expect($file->getClientFilename())->toBe('README.txt');
        });
    });
    describe('->getClientMediaType', function() {
        it('returns the client type', function() {
            $file = new UploadedFile('', 0, UPLOAD_ERR_NO_FILE, NULL, 'text/plain');
            expect($file->getClientMediaType())->toBe('text/plain');
        });
    });
});