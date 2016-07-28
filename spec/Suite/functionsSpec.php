<?php

use Kahlan\Plugin\Monkey;
use function Ochenta\resource_of;
use function Ochenta\mimetype_of;

describe('resource_of', function() {
    it('returns null when null given', function() {
        expect(resource_of(null))->toBeNull();
    });
});

describe('mimetype_of', function() {
    it('throws RuntimeException when null given', function() {
        expect(function() { mimetype_of(null); })->toThrow(new RuntimeException);
    });

    // Disabled because Monkey::patch doesn't work on functions
    xit('throws RuntimeException when finfo db is not available', function() {
        Monkey::patch('finfo_open', function() {
            return false;
        });

        expect(function() { mimetype_of('Hello World'); })->toThrow(new RuntimeException);
    });

    it('returns mimetype from fileinfo db if the file is found', function() {
        $tmpfile = tempnam(sys_get_temp_dir(), 'kahlan/');
        skipIf($tmpfile === false);
        $tmphandle = fopen($tmpfile, 'a');
        fwrite($tmphandle, 'Hello World');
        fclose($tmphandle);
        expect(mimetype_of(null, $tmpfile))->toBe('text/plain');
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
