<?php

use function Ochenta\resource_of;

describe('resource_of', function() {
    it('returns null when null given', function() {
        expect(resource_of(null))->toBeNull();
    });
});
