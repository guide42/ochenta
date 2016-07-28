<?php

use Kahlan\Plugin\Monkey;
use Ochenta\ServerRequest;

describe('ServerRequest', function() {
    describe('->__construct', function() {
        it('assigns method from REQUEST_METHOD', function() {
            expect((new ServerRequest(['REQUEST_METHOD' => 'HEAD']))->getMethod())->toBe('HEAD');
        });

        it('assigns uri from REQUEST_URI', function() {
            $req = new ServerRequest([
              'REQUEST_URI' => 'http://user:pass@example.com/path',
            ]);

            expect($req->getTarget())->toBe('/path');
            expect($req->getHeaders()['HOST'])->toBe(['example.com']);
        });

        it('assigns query as query parameters', function() {
            $req = new ServerRequest(null, ['foo' => 'bar']);

            expect($req->getQuery())->toBeA('array')->toContainKey('foo');
            expect($req->getQuery()['foo'])->toBe('bar');
        });

        it('assigns query from query string', function() {
            $req = new ServerRequest(['REQUEST_URI' => '/path?foo=bar'], []);

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

        it('throws UnexpectedValueException with invalid uploaded file', function() {
            expect(function() {
              new ServerRequest(null, null, null, ['/tmp/phpXXXXXX']);
            })
            ->toThrow(new UnexpectedValueException);
        });

        it('throws UnexpectedValueException with an uploaded file without error', function() {
            expect(function() {
              new ServerRequest(null, null, null, [['tmp_name' => '/tmp/phpXXXXXX']]);
            })
            ->toThrow(new UnexpectedValueException);
        });

        it('assigns simple files', function() {
            $req = new ServerRequest(null, null, null, [
              'avatar' => [
                'tmp_name' => '/tmp/phpUxcOty',
                'name'     => 'avatar.png',
                'type'     => 'image/png',
                'size'     => 73096,
                'error'    => 0,
              ],
            ]);

            expect($req->getFiles())->toBeA('array')->toContainKey('avatar');
            expect($req->getFiles()['avatar'])->toBeA('array')->toContainKey('tmp_name');
            expect($req->getFiles()['avatar']['tmp_name'])->toBe('/tmp/phpUxcOty');
        });

        it('assigns collection files', function() {
            $req = new ServerRequest(null, null, null, [
              'avatars' => [
                'tmp_name' => [0 => '/tmp/phpLTufCb', 1 => '/tmp/phpW5Lk9D'],
                'name'     => [0 => 'avatar-0.png', 1 => 'avatar-1.png'],
                'type'     => [0 => 'image/png', 1 => 'image/png'],
                'size'     => [0 => 73097, 1 => 73098],
                'error'    => [0 => 0, 1 => 0],
              ],
            ]);

            expect($req->getFiles())->toBeA('array')->toContainKey('avatars');
            expect($req->getFiles()['avatars'])->toBeA('array')->toContainKey(0);
            expect($req->getFiles()['avatars'][0])->toBeA('array')->toContainKey('tmp_name');
            expect($req->getFiles()['avatars'][0]['tmp_name'])->toBe('/tmp/phpLTufCb');
        });

        it('assigns nested simple files', function() {
            $req = new ServerRequest(null, null, null, [
              'someform' => [
                'tmp_name' => ['avatar' => '/tmp/phpUxcOty'],
                'name'     => ['avatar' => 'avatar.png'],
                'type'     => ['avatar' => 'image/png'],
                'size'     => ['avatar' => 73096],
                'error'    => ['avatar' => 0],
              ],
            ]);

            expect($req->getFiles())->toBeA('array')->toContainKey('someform');
            expect($req->getFiles()['someform'])->toBeA('array')->toContainKey('avatar');
            expect($req->getFiles()['someform']['avatar'])->toBeA('array')->toContainKey('tmp_name');
            expect($req->getFiles()['someform']['avatar']['tmp_name'])->toBe('/tmp/phpUxcOty');
        });

        it('assigns nested collection files', function() {
            $req = new ServerRequest(null, null, null, [
              'someform' => [
                'tmp_name' => ['avatars' => [0 => '/tmp/phpLTufCb', 1 => '/tmp/phpW5Lk9D']],
                'name'     => ['avatars' => [0 => 'avatar-0.png', 1 => 'avatar-1.png']],
                'type'     => ['avatars' => [0 => 'image/png', 1 => 'image/png']],
                'size'     => ['avatars' => [0 => 73097, 1 => 73098]],
                'error'    => ['avatars' => [0 => 0, 1 => 0]],
              ],
            ]);

            expect($req->getFiles())->toBeA('array')->toContainKey('someform');
            expect($req->getFiles()['someform'])->toBeA('array')->toContainKey('avatars');
            expect($req->getFiles()['someform']['avatars'])->toBeA('array')->toContainKey(0);
            expect($req->getFiles()['someform']['avatars'][0])->toBeA('array')->toContainKey('tmp_name');
            expect($req->getFiles()['someform']['avatars'][0]['tmp_name'])->toBe('/tmp/phpLTufCb');
        });

        it('assigns the body as a resource if string given', function() {
            $req = new ServerRequest(null, null, null, null, 'Hello World');

            expect($req->getBody())->toBeA('resource');
            expect(stream_get_contents($req->getBody()))->toBe('Hello World');
        });

        it('assigns the body to be php://input when it has Content-Length', function() {
            Monkey::patch('fopen', function($filename, $mode) {
                if ($filename === 'php://input') {
                    $input = fopen('php://temp', 'r+');
                    fwrite($input, 'Hello World');
                    fseek($input, 0);
                    return $input;
                }
                return fopen($filename, $mode);
            });

            $req = new ServerRequest([
                'CONTENT_TYPE' => 'text/plain',
                'CONTENT_LENGTH' => 11,
            ]);

            expect($req->getBody())->toBeA('resource');
            expect(fread($req->getBody(), 11))->toBe('Hello World');
        });

        it('assigns the body to the given and is not overriden even if it has Content-Length', function() {
            Monkey::patch('fopen', function($filename, $mode) {
                if ($filename === 'php://input') {
                    throw RuntimeExpection('This should never be called');
                }
                return fopen($filename, $mode);
            });

            $req = new ServerRequest([
                'CONTENT_TYPE' => 'text/plain',
                'CONTENT_LENGTH' => 11,
            ], null, null, null, 'Hello World');

            expect($req->getBody())->toBeA('resource');
            expect(fread($req->getBody(), 11))->toBe('Hello World');
        });
    });

    describe('->getParsedBody', function() {
        it('returns parsed body when is application/x-www-form-urlencoded', function() {
            Monkey::patch('fopen', function($filename, $mode) {
                if ($filename === 'php://input') {
                    $input = fopen('php://temp', 'r+');
                    fwrite($input, 'hello=world&foo=bar');
                    fseek($input, 0);
                    return $input;
                }
                return fopen($filename, $mode);
            });

            $req = new ServerRequest([
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'CONTENT_LENGTH' => 17,
            ]);

            expect($req->getParsedBody())->toBe(['hello' => 'world', 'foo' => 'bar']);
        });
    });

    describe('->normalizeServer', function() {
        it('returns HTTPS when SCRIPT_URI starts with https://', function() {
            $req = new ServerRequest([
                'HTTPS' => 'off',
                'SCRIPT_URI' => 'https://ochenta/',
            ]);

            expect($req->getUri())->toContainKey('scheme');
            expect($req->getUri()['scheme'])->toBe('https');
        });

        it('returns REQUEST_METHOD overriden by HTTP_X_HTTP_METHOD_OVERRIDE if present', function() {
            $req = new ServerRequest([
                'REQUEST_METHOD' => 'POST',
                'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT',
            ]);

            expect($req->getMethod())->toBe('PUT');
        });
    });

    describe('->normalizeUrl', function() {
        it('throws UnexpectedValueException on invalid REQUEST_URI', function() {
            expect(function() {
                new ServerRequest([
                    'REQUEST_URI' => 'http://@/',
                ]);
            })
            ->toThrow(new UnexpectedValueException);
        });
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

        it('returns host and port from HOST header', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => '/path?queryString',
                'HTTP_HOST' => 'ochenta:8080',
            ]);

            expect($req->getUri())->toContainKey('host');
            expect($req->getUri()['host'])->toBe('ochenta');
            expect($req->getUri()['port'])->toBe(8080);
        });

        it('returns host from HOST header but not port when is 80', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => '/path?queryString',
                'HTTP_HOST' => 'ochenta:80',
            ]);

            expect($req->getUri())->toContainKey('host');
            expect($req->getUri()['host'])->toBe('ochenta');
            expect($req->getUri())->not->toContainKey('port');
        });

        it('returns host from HOST header but not port when is 443 in HTTPS', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => 'https://ochenta/path?queryString',
                'HTTP_HOST' => 'ochenta:80',
                'HTTPS' => 'on',
            ]);

            expect($req->getUri())->toContainKey('host');
            expect($req->getUri()['host'])->toBe('ochenta');
            expect($req->getUri())->not->toContainKey('port');
        });

        it('returns host in lowercase', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => '/path?queryString',
                'HTTP_HOST' => 'OcHeNtA',
            ]);

            expect($req->getUri())->toContainKey('host');
            expect($req->getUri()['host'])->toBe('ochenta');
        });

        it('throws UnexpectedValueException when invalid host', function() {
            expect(function() {
                new ServerRequest([
                    'REQUEST_URI' => 'http://☃.com/path?queryString',
                ]);
            })
            ->toThrow(new UnexpectedValueException);
        });

        it('returns port from SERVER_PORT environment variable', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => '/path?queryString',
                'SERVER_PORT' => '443',
            ]);

            expect($req->getUri())->toContainKey('port');
            expect($req->getUri()['port'])->toEqual(443);
        });

        it('doesn\'t returns port when SERVER_PORT is 80', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => '/path?queryString',
                'SERVER_PORT' => 80,
            ]);

            expect($req->getUri())->not->toContainKey('port');
        });

        it('doesn\'t returns port when SERVER_PORT is 443 in HTTPS', function() {
            $req = new ServerRequest([
                'REQUEST_URI' => 'https://example.com/path?queryString',
                'SERVER_PORT' => 443,
                'HTTPS' => 'on',
            ]);

            expect($req->getUri())->not->toContainKey('port');
        });
    });
});