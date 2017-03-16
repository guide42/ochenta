<?php declare(strict_types=1);

use Kahlan\Plugin\Double;
use ochenta\psr7\RequestTrait;

describe('psr7\\RequestTrait', function() {
    describe('->getRequestTarget', function() {
        it('returns request target, default empty string', function() {
            expect((Double::instance(['uses' => RequestTrait::class]))->getRequestTarget())->toBe('');
        });
        it('returns given request target', function() {
            $req = Double::instance(['uses' => RequestTrait::class]);
            $req = $req->withRequestTarget('/path');

            expect($req->getRequestTarget())->toBe('/path');
        });
    });
});