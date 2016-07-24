<?php

use Ochenta\ServerRequest;
use Ochenta\UploadedFile;

describe('ServerRequest', function() {
    describe('->__construct', function() {
        it('assigns method from REQUEST_METHOD', function() {
            expect((new ServerRequest(['REQUEST_METHOD' => 'HEAD']))->getMethod())->toBe('HEAD');
        });

        it('assigns uri from REQUEST_URI', function() {
            $req = new ServerRequest([
              'REQUEST_URI' => 'http://user:pass@example.com/path?queryString',
            ]);

            expect($req->getTarget())->toBe('/path');
            expect($req->getHeaders()['HOST'])->toBe(['example.com']);
        });

        it('assigns query as query parameters', function() {
            $req = new ServerRequest(null, ['foo' => 'bar']);

            expect($req->getQuery())->toBeA('array')->toContainKey('foo');
            expect($req->getQuery()['foo'])->toBe('bar');
        });

        it('assigns xargs as form parameters', function() {
            $req = new ServerRequest(null, null, ['foo' => 'bar']);

            expect($req->getParsedBody())->toBeA('array')->toContainKey('foo');
            expect($req->getParsedBody()['foo'])->toBe('bar');
        });

        it('assigns content headers from server', function() {
            $req = new ServerRequest([
                'CONTENT_TYPE'   => 'text/plain',
                'CONTENT_LENGTH' => 0,
            ]);

            expect($req->getHeaders())->toBeA('array')->toContainKey('CONTENT-TYPE')->toContainKey('CONTENT-LENGTH');
            expect($req->getHeaders()['CONTENT-TYPE'])->toBe(['text/plain']);
            expect($req->getHeaders()['CONTENT-LENGTH'])->toBe([0]);
        });

        it('throws InvalidArgumentException with invalid uploaded file', function() {
            expect(function() {
              new ServerRequest(null, null, null, ['/tmp/phpXXXXXX']);
            })
            ->toThrow(new InvalidArgumentException);
        });

        it('assigns simple files', function() {
            $req = new ServerRequest(null, null, null, [
              'avatar' => array(
                'tmp_name' => '/tmp/phpUxcOty',
                'name'     => 'avatar.png',
                'type'     => 'image/png',
                'size'     => 73096,
                'error'    => 0,
              ),
            ]);

            expect($req->getFiles())->toBeA('array')->toContainKey('avatar');
            expect($req->getFiles()['avatar'])->toBeAnInstanceOf(UploadedFile::class);
        });

        it('assigns collection files', function() {
            $req = new ServerRequest(null, null, null, [
              'avatars' => array(
                'tmp_name' => [0 => '/tmp/phpLTufCb', 1 => '/tmp/phpW5Lk9D'],
                'name'     => [0 => 'avatar-0.png', 1 => 'avatar-1.png'],
                'type'     => [0 => 'image/png', 1 => 'image/png'],
                'size'     => [0 => 73097, 1 => 73098],
                'error'    => [0 => 0, 1 => 0],
              ),
            ]);

            expect($req->getFiles())->toBeA('array')->toContainKey('avatars');
            expect($req->getFiles()['avatars'])->toBeA('array');
            expect($req->getFiles()['avatars'][0])->toBeAnInstanceOf(UploadedFile::class);
        });

        it('assigns nested simple files', function() {
            $req = new ServerRequest(null, null, null, [
              'someform' => array(
                'tmp_name' => array('avatar' => '/tmp/phpUxcOty'),
                'name'     => array('avatar' => 'avatar.png'),
                'type'     => array('avatar' => 'image/png'),
                'size'     => array('avatar' => 73096),
                'error'    => array('avatar' => 0),
              ),
            ]);

            expect($req->getFiles())->toBeA('array')->toContainKey('someform');
            expect($req->getFiles()['someform'])->toBeA('array')->toContainKey('avatar');
            expect($req->getFiles()['someform']['avatar'])->toBeAnInstanceOf(UploadedFile::class);
        });

        it('assigns nested collection files', function() {
            $req = new ServerRequest(null, null, null, [
              'someform' => array(
                'tmp_name' => array('avatars' => [0 => '/tmp/phpLTufCb', 1 => '/tmp/phpW5Lk9D']),
                'name'     => array('avatars' => [0 => 'avatar-0.png', 1 => 'avatar-1.png']),
                'type'     => array('avatars' => [0 => 'image/png', 1 => 'image/png']),
                'size'     => array('avatars' => [0 => 73097, 1 => 73098]),
                'error'    => array('avatars' => [0 => 0, 1 => 0]),
              ),
            ]);

            expect($req->getFiles())->toBeA('array')->toContainKey('someform');
            expect($req->getFiles()['someform'])->toBeA('array')->toContainKey('avatars');
            expect($req->getFiles()['someform']['avatars'])->toBeA('array');
            expect($req->getFiles()['someform']['avatars'][0])->toBeAnInstanceOf(UploadedFile::class);
        });
    });

    describe('->normalizeUrl', function() {
        it('returns parse_url parts from REQUEST_URI', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => 'http://user:pass@example.com/path?queryString',
            ]);

            expect($req->getUri())->toBeA('array');
        });

        it('returns scheme from HTTPS server variable', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => '//user:pass@example.com/path?queryString',
                'HTTPS' => 'on',
            ]);

            expect($req->getUri())->toContainKey('scheme');
            expect($req->getUri()['scheme'])->toBe('https');
        });

        it('returns user and pass from PHP_AUTH environment variable', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => 'http://example.com/path?queryString',
                'PHP_AUTH_USER' => 'root',
                'PHP_AUTH_PW' => 'toor',
            ]);

            expect($req->getUri())->toContainKey('user')->toContainKey('pass');
            expect($req->getUri()['user'])->toBe('root');
            expect($req->getUri()['pass'])->toBe('toor');
        });

        it('returns host from HOST header', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => '/path?queryString',
                'HTTP_HOST' => 'ochenta',
            ]);

            expect($req->getUri())->toContainKey('host');
            expect($req->getUri()['host'])->toBe('ochenta');
        });

        it('returns host from SERVER_NAME environment variable', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => '/path?queryString',
                'SERVER_NAME' => 'ochenta',
            ]);

            expect($req->getUri())->toContainKey('host');
            expect($req->getUri()['host'])->toBe('ochenta');
        });

        it('returns host, defaults to localhost', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => '/path?queryString',
            ]);

            expect($req->getUri())->toContainKey('host');
            expect($req->getUri()['host'])->toBe('localhost');
        });

        it('returns port from SERVER_PORT environment variable', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => '/path?queryString',
                'SERVER_PORT' => 443,
            ]);

            expect($req->getUri())->toContainKey('port');
            expect($req->getUri()['port'])->toEqual(443);
        });
    });
});