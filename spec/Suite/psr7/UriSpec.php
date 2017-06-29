<?php declare(strict_types=1);

use Kahlan\Plugin\Double;
use Psr\Http\Message\UriInterface;
use ochenta\psr7\Uri;

describe('psr7\\Uri', function() {
    describe('->__toString', function() {
        it('returns full uri', function() {
            $uri = 'http://user:pass@ochenta:8080/path?queryString#fragment';
            expect((new Uri($uri))->__toString())->toBe($uri);
        });
    });
    describe('->__construct', function() {
        it('accepts nothing to create an empty uri', function() {
            $uri = new Uri;
            expect($uri->getScheme())->toBe('');
            expect($uri->getAuthority())->toBe('');
            expect($uri->getUserInfo())->toBe('');
            expect($uri->getPath())->toBe('');
            expect($uri->getQuery())->toBe('');
            expect($uri->getFragment())->toBe('');
        });
        it('accepts an array with the path components', function() {
            $uri = new Uri(['path' => '/', 'query' => 'foo&bar']);
            expect($uri->getPath())->toBe('/');
            expect($uri->getQuery())->toBe('foo&bar');
        });
        it('accepts an instance of iself and extracts the components', function() {
            $uri0 = new Uri('/');
            $uri1 = new Uri($uri0);

            expect($uri1->getPath())->toBe('/');
        });
        it('accepts an instance of UriInterface and re-generate the components', function() {
            $uri0 = Double::instance(['implements' => [UriInterface::class]]);
            allow($uri0)->toReceive('getPath')->andReturn('/');
            allow($uri0)->toReceive('getUserInfo')->andReturn('');
            $uri1 = new Uri($uri0);

            expect($uri1->getPath())->toBe('/');
        });
        it('throws InvalidArgumentException when uri is malformed', function() {
            expect(function() {
                new Uri('https://@/path');
            })
            ->toThrow(new InvalidArgumentException);
        });
        it('throws InvalidArgumentException when uri is invalid', function() {
            expect(function() {
                new Uri(fopen('php://memory', 'r'));
            })
            ->toThrow(new InvalidArgumentException);
        });
        it('throws InvalidArgumentException when given scheme is not allowed', function() {
            expect(function() {
                new Uri('foobar://ochenta/');
            })
            ->toThrow(new InvalidArgumentException);
        });
    });
    describe('->extract', function() {
        it('returns the resource', function() {
            expect((new Uri(['path' => '/']))->extract())->toBe(['path' => '/']);
        });
    });
    describe('->extend', function() {
        it('returns a new instance with the result of the callback', function() {
            $uri0 = new Uri('/');
            $uri1 = $uri0->extend(function(UriInterface $uri2) use($uri0) {
                expect($uri0)->toBe($uri0);
                return '/path';
            });

            expect($uri0->getPath())->toBe('/');
            expect($uri1->getPath())->toBe('/path');
        });
    });
    describe('->getAuthority', function() {
        it('returns user info, host and port', function() {
            expect((new Uri('http://user:path@ochenta:8080/path'))->getAuthority())->toBe('user:path@ochenta:8080');
        });
        it('returns authority without path if it\'s standard', function() {
            expect((new Uri('http://ochenta:80/path'))->getAuthority())->toBe('ochenta');
        });
    });
    describe('->getUserInfo', function() {
        it('returns user', function() {
            expect((new Uri('http://user@ochenta:8080/path'))->getUserInfo())->toBe('user');
        });
        it('returns user and pass', function() {
            expect((new Uri('http://user:path@ochenta:8080/path'))->getUserInfo())->toBe('user:path');
        });
    });
    describe('->getHost', function() {
        it('returns the host in lowercase', function() {
            expect((new Uri('http://oChEnTa:8080'))->getHost())->toBe('ochenta');
        });
    });
    describe('->getQuery', function() {
        it('returns an empty string if query is not found', function() {
            expect((new Uri('/'))->getQuery())->toBe('');
        });
        it('returns query if found', function() {
            expect((new Uri('/?foo=bar'))->getQuery())->toBe('foo=bar');
        });
    });
    describe('->getFragment', function() {
        it('returns an empty string if fragment is not found', function() {
            expect((new Uri('/'))->getFragment())->toBe('');
        });
        it('returns fragment if found', function() {
            expect((new Uri('/?foo=bar#foobar'))->getFragment())->toBe('foobar');
        });
    });
    describe('->withScheme', function() {
        it('returns a new instance with the given scheme', function() {
            $uri0 = new Uri('http://ochenta/');
            $uri1 = $uri0->withScheme('https');

            expect($uri0->getScheme())->toBe('http');
            expect($uri1->getScheme())->toBe('https');
        });
    });
    describe('->withUserInfo', function() {
        it('returns a new instance with the given user and password', function() {
            $uri0 = new Uri('http://ochenta/');
            $uri1 = $uri0->withUserInfo('user', 'pass');

            expect($uri0->getUserInfo())->toBe('');
            expect($uri1->getUserInfo())->toBe('user:pass');
        });
    });
    describe('->withHost', function() {
        it('returns a new instance with the given host', function() {
            $uri0 = new Uri('http://localhost/');
            $uri1 = $uri0->withHost('ochenta');

            expect($uri0->getHost())->toBe('localhost');
            expect($uri1->getHost())->toBe('ochenta');
        });
    });
    describe('->withPort', function() {
        it('returns a new instance with the given port', function() {
            $uri0 = new Uri('http://ochenta/');
            $uri1 = $uri0->withPort('8080');

            expect($uri0->getPort())->toBe(NULL);
            expect($uri1->getPort())->toBe(8080);
        });
    });
    describe('->withPath', function() {
        it('returns a new instance with the given path', function() {
            $uri0 = new Uri('http://ochenta/');
            $uri1 = $uri0->withPath('/path');

            expect($uri0->getPath())->toBe('/');
            expect($uri1->getPath())->toBe('/path');
        });
    });
    describe('->withQuery', function() {
        it('returns a new instance with the given query', function() {
            $uri0 = new Uri('http://ochenta/');
            $uri1 = $uri0->withQuery('foo=bar');

            expect($uri0->getQuery())->toBe('');
            expect($uri1->getQuery())->toBe('foo=bar');
        });
    });
    describe('->withFragment', function() {
        it('returns a new instance with the given fragment', function() {
            $uri0 = new Uri('http://ochenta/');
            $uri1 = $uri0->withFragment('foobar');

            expect($uri0->getFragment())->toBe('');
            expect($uri1->getFragment())->toBe('foobar');
        });
    });
});