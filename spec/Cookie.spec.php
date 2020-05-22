<?php declare(strict_types=1);

use ochenta\{Cookie, ServerRequest};

describe('Cookie', function() {
    describe('->__construct', function() {
        it('accepts name and value', function() {
            $cookie = new Cookie('foo', 'bar');

            expect($cookie->getName())->toBe('foo');
            expect($cookie->getValue())->toBe('bar');
        });

        it('throws InvalidArgumentException on invalid name', function() {
            foreach (["foo=bar", "foo;bar", "foo,bar", "foo\n"] as $name) {
                expect(function() use($name) {
                    new Cookie($name, 'bar');
                })
                ->toThrow(new \InvalidArgumentException);
            }
        });

        it('accepts expires attribute as unix timestamp', function() {
            expect((new Cookie('foo', 'bar', ['Expires' => time() + 3600]))->getLifetime())->toBe(3600);
            expect((new Cookie('foo', 'bar', ['Expires' => time() - 3600]))->getLifetime())->toBe(-3600);
        });

        it('accepts expires attribute as DateTime object', function() {
            expect((new Cookie('foo', 'bar', ['Expires' => new \DateTime('+1 hour')]))->getLifetime())->toBe(3600);
            expect((new Cookie('foo', 'bar', ['Expires' => new \DateTime('-1 hour')]))->getLifetime())->toBe(-3600);
        });
    });

    describe('->getPrefix', function() {
        it('returns empty string on unsecure cookies', function() {
            expect((new Cookie('foo', 'bar', ['Secure' => FALSE]))->getPrefix())->toBe('');
        });

        it('returns secure prefix when on a secure cookie but limited by path', function() {
            expect((new Cookie('foo', 'bar', ['Secure' => TRUE, 'Path' => '/limited/']))->getPrefix())->toBe('__Secure-');
        });

        it('returns secure prefix when on a secure cookie but limited by domain', function() {
            expect((new Cookie('foo', 'bar', ['Secure' => TRUE, 'Domain' => '.example.com']))->getPrefix())->toBe('__Secure-');
        });

        it('returns host prefix when on a secure cookie and not limited', function() {
            expect((new Cookie('foo', 'bar', ['Secure' => TRUE, 'Path' => '/']))->getPrefix())->toBe('__Host-');
        });
    });

    describe('->getName', function() {
        it('returns cookie name', function() {
            expect((new Cookie('foo', 'bar'))->getName())->toBe('foo');
        });
    });

    describe('->getValue', function() {
        it('returns value as string', function() {
            expect((new Cookie('foo', strval(123)))->getValue())->toBe('123');
            expect((new Cookie('foo', strval(123.456)))->getValue())->toBe('123.456');
        });
    });

    describe('->getLifetime', function() {
        it('returns null when doesn\'t expire', function() {
            expect((new Cookie('foo', 'bar'))->getLifetime())->toBe(NULL);
        });

        it('returns expire minus now', function() {
            $date = new \DateTime;
            $cookie = new Cookie('foo', 'bar', ['Expires' => $date], [], $date->getTimestamp());

            expect($cookie->getLifetime())->toBe(0);
        });
    });

    describe('->isExpired', function() {
        it('returns false when doesn\'t expire', function() {
            expect((new Cookie('foo', 'bar'))->isExpired())->toBe(FALSE);
        });

        it('returns false when expires after now', function() {
            $cookie = new Cookie('foo', 'bar',
                ['Expires' => new \DateTime('+1 hour')], [],
                (new \DateTime('-1 hour'))->getTimestamp()
            );

            expect($cookie->isExpired())->toBe(FALSE);
        });

        it('returns true when expires before now', function() {
            $cookie = new Cookie('foo', 'bar',
                ['Expires' => new \DateTime('-1 hour')], [],
                (new \DateTime('+1 hour'))->getTimestamp()
            );

            expect($cookie->isExpired())->toBe(TRUE);
        });
    });

    describe('->isSecure', function() {
        it('returns Secure attribute', function() {
            expect((new Cookie('foo', 'bar', ['Secure' => TRUE]))->isSecure())->toBe(TRUE);
            expect((new Cookie('foo', 'bar', ['Secure' => FALSE]))->isSecure())->toBe(FALSE);
        });
    });

    describe('->matches', function() {
        it('returns false when is expired', function() {
            $cookie = new Cookie('foo', 'bar',
                ['Expires' => new \DateTime('-1 hour')], [],
                (new \DateTime('+1 hour'))->getTimestamp()
            );

            expect($cookie->matches(new ServerRequest))->toBe(FALSE);
        });

        it('returns false when cookie domain is grather than request host', function() {
            $cookie = new Cookie('foo', 'bar', ['Domain' => 'one.example.com']);
            $request = new ServerRequest(['HTTP_HOST' => 'example.com']);

            expect($cookie->matches($request))->toBe(FALSE);
        });

        it('returns false when one part in cookie domain is different than same request host part', function() {
            $cookie = new Cookie('foo', 'bar', ['Domain' => 'two.one.example.com']);
            $request = new ServerRequest(['HTTP_HOST' => 'three.foo.one.example.com']);

            expect($cookie->matches($request))->toBe(FALSE);
        });

        it('returns false when host-only flag is on and cookie domain and request host are not equal', function() {
            $cookie = new Cookie('foo', 'bar', ['Domain' => 'one.example.com'], ['host-only' => TRUE]);
            $request = new ServerRequest(['HTTP_HOST' => 'foo.one.example.com']);

            expect($cookie->matches($request))->toBe(FALSE);
        });

        it('returns false when request path doesn\'t start with cookie path', function() {
            $cookie = new Cookie('foo', 'bar', ['Path' => '/bye']);
            $request = new ServerRequest(['REQUEST_URI' => '/good/bye']);

            expect($cookie->matches($request))->toBe(FALSE);
        });

        it('returns false when cookie is secure but request is not', function() {
            $cookie = new Cookie('foo', 'bar', ['Secure' => TRUE]);
            $request = new ServerRequest(['HTTPS' => 'off']);

            expect($cookie->matches($request))->toBe(FALSE);
        });

        it('returns true when is not expired', function() {
            expect((new Cookie('foo', 'bar'))->matches(new ServerRequest(['HTTPS' => 'on'])))->toBe(TRUE);
        });
    });

    describe('->prepare', function() {
        it('returns an instance of Cookie', function() {
            expect((new Cookie('foo', 'bar'))->prepare(new ServerRequest))->toBeAnInstanceOf(Cookie::class);
        });

        it('returns Cookie with request host as domain', function() {
            $request = new ServerRequest(['HTTP_HOST' => 'example.com']);
            $cookie = (new Cookie('foo', 'bar'))->prepare($request);

            expect($cookie->__toString())->toMatch('/; Domain=example.com/');
        });

        it('returns Cookie with request target path as path', function() {
            $request = new ServerRequest(['REQUEST_URI' => '/foobar.html']);
            $cookie = (new Cookie('foo', 'bar'))->prepare($request);

            expect($cookie->__toString())->toMatch('/; Path=\/foobar.html/');
        });

        it('returns Cookie with secure attribute true if request is HTTPS', function() {
            $request = new ServerRequest(['HTTPS' => 'on']);
            $cookie = (new Cookie('foo', 'bar'))->prepare($request);

            expect($cookie->__toString())->toMatch('/; Secure/');
        });

        it('returns Cookie with http-only attribute true if request is a ServerRequest', function() {
            expect(((new Cookie('foo', 'bar'))->prepare(new ServerRequest))->__toString())->toMatch('/; HttpOnly/');
        });

        it('returns Cookie with host-only flag true', function() {
            $requestPrepare = new ServerRequest(['HTTP_HOST' => 'one.example.com']);
            $requestMatches = new ServerRequest(['HTTP_HOST' => 'foo.one.example.com']);

            expect(((new Cookie('foo', 'bar'))->prepare($requestPrepare))->matches($requestMatches))->toBe(FALSE);
        });

        it('returns Cookie with given expires', function() {
            $expires = new \DateTime('2019-12-31 23:42:00', new \DateTimeZone('UTC'));
            $cookie = (new Cookie('foo', 'bar'))->prepare(new ServerRequest, $expires);

            expect($cookie->__toString())->toMatch('/; Expires=Tuesday, 31-Dec-2019 23:42:00 GMT/');
        });
    });

    describe('->__toString', function() {
        it('returns key and value', function() {
            $cookie = new Cookie('foo', 'bar', [
                'Path' => NULL,
                'Domain' => NULL,
                'Secure' => FALSE,
                'HttpOnly' => FALSE,
            ]);

            expect($cookie->__toString())->toBe('foo=bar');
        });

        it('returns Path attribute if is set', function() {
            $cookie = new Cookie('foo', 'bar', [
                'Path' => '/',
                'Domain' => NULL,
                'Secure' => FALSE,
                'HttpOnly' => FALSE,
            ]);

            expect($cookie->__toString())->toBe('foo=bar; Path=/');
        });

        it('returns Domain attribute if is set', function() {
            $cookie = new Cookie('foo', 'bar', [
                'Path' => '/',
                'Domain' => 'localhost',
                'Secure' => FALSE,
                'HttpOnly' => FALSE,
            ]);

            expect($cookie->__toString())->toBe('foo=bar; Path=/; Domain=localhost');
        });

        it('returns Secure attribute if true', function() {
            $cookie = new Cookie('foo', 'bar', [
                'Path' => '/',
                'Domain' => 'localhost',
                'Secure' => TRUE,
                'HttpOnly' => FALSE,
            ]);

            expect($cookie->__toString())->toBe('__Secure-foo=bar; Path=/; Domain=localhost; Secure');
        });

        it('returns HttpOnly attribute if true', function() {
            $cookie = new Cookie('foo', 'bar', [
                'Path' => '/',
                'Domain' => 'localhost',
                'Secure' => TRUE,
                'HttpOnly' => TRUE,
            ]);

            expect($cookie->__toString())->toBe('__Secure-foo=bar; Path=/; Domain=localhost; Secure; HttpOnly');
        });

        it('returns Expires attribute if is set', function() {
            $expires = new \DateTime('2019-12-31 23:42:00', new \DateTimeZone('UTC'));
            $cookie = new Cookie('foo', 'bar', [
                'Expires' => $expires,
                'Secure' => TRUE,
                'HttpOnly' => TRUE,
            ]);

            expect($cookie->__toString())->toBe('__Secure-foo=bar; Expires=Tuesday, 31-Dec-2019 23:42:00 GMT; Secure; HttpOnly');
        });

        it('returns deleted as value and Expires attribute in the past if cookie value is empty string', function() {
            $now = new \DateTime('2019-01-01 12:21:00', new \DateTimeZone('UTC'));
            $cookie = new Cookie('foo', '', ['Secure' => FALSE, 'HttpOnly' => FALSE], [], $now->getTimestamp());

            expect($cookie->__toString())->toBe('foo=deleted; Expires=Monday, 01-Jan-2018 12:20:18 GMT; Max-Age=0');
        });
    });
});