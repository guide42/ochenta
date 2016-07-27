<?php

use function Ochenta\resource_for;

describe('resource_for', function() {
    it('returns null when null given', function() {
        expect(resource_for(null))->toBeNull();
    });
});
