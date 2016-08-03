<?php

use Kahlan\Plugin\Monkey;
use function Ochenta\resource_of;
use function Ochenta\mimetype_of;
use function Ochenta\hash;

describe('resource_of', function() {
    it('returns null when null given', function() {
        expect(resource_of(NULL))->toBeNull();
    });
});

describe('mimetype_of', function() {
    it('throws RuntimeException when null given', function() {
        expect(function() { mimetype_of(NULL); })->toThrow(new RuntimeException);
    });

    // Disabled because Monkey::patch doesn't work on included files
    xit('throws RuntimeException when finfo db is not available', function() {
        Monkey::patch('finfo_open', function() {
            return FALSE;
        });

        expect(function() { mimetype_of('Hello World'); })->toThrow(new RuntimeException);
    });

    it('returns mimetype from fileinfo db if the file is found', function() {
        $tmpfile = tempnam(sys_get_temp_dir(), 'kahlan/');
        skipIf($tmpfile === FALSE);
        $tmphandle = fopen($tmpfile, 'a');
        fwrite($tmphandle, 'Hello World');
        fclose($tmphandle);
        expect(mimetype_of(NULL, $tmpfile))->toBe('text/plain');
        unlink($tmpfile);
    });

    it('returns deduced mimetype of string', function() {
        expect(mimetype_of('Hello World'))->toBe('text/plain');
        expect(mimetype_of("\xff\xd8\xffbla.txt"))->toBe('image/jpeg');
    });

    it('returns deduced mimetype of resource', function() {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'Hello World');
        expect(mimetype_of($resource))->toBe('text/plain');
        fclose($resource);
    });
});

describe('hash', function() {
    it('throws InvalidArgumentException when null given', function() {
        expect(function() { hash(NULL); })->toThrow(new InvalidArgumentException);
    });

    it('throws InvalidArgumentException when the given resource is not seekable', function() {
        expect(function() { hash(popen('php --version', 'r')); })->toThrow(new InvalidArgumentException);
    });

    it('returns hash of an scalar', function() {
        expect(hash(42))->toBe('a1d0c6e83f027327d8461063f4ac58a6');
        expect(hash('Hello World'))->toBe('b10a8db164e0754105b7a99be72e3fe5');
    });

    it('returns hash of a resource', function() {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'Hello World');
        expect(hash($resource))->toBe('b10a8db164e0754105b7a99be72e3fe5');
    });
});
