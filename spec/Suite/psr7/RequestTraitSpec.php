<?php declare(strict_types=1);

use Kahlan\Plugin\Stub;
use ochenta\psr7\RequestTrait;

describe('psr7\\RequestTrait', function() {
    describe('->getRequestTarget', function() {
        it('returns request target, default empty string', function() {
            expect((Stub::create(['uses' => RequestTrait::class]))->getRequestTarget())->toBe('');
        });
        it('returns given request target', function() {
            $req = Stub::create(['uses' => RequestTrait::class]);
            $req = $req->withRequestTarget('/path');

            expect($req->getRequestTarget())->toBe('/path');
        });
    });
});