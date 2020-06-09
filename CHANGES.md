# Changes for Ochenta

All notable changes to this project will be documented in this file.

## [0.7.0]

## [0.6.0] - 2020-06-09

- Adds `cookie` middleware.
- Adds `accept` functions to do content negotiation.
- New `Request::isMethod` and `Request::isJSON` and `Request::isAJAX`.
- New `Request::getAcceptMediaType`, `::getAcceptCharset`, `::getAcceptEncoding` and `::getAcceptLanguage`.
- [BC-BREAK] Now `ServerRequest::getFiles` ignores empty files.
- Now status code 308 (Permanent Redirect) can be used for redirect.
- Adds `Response::isEmpty`.

## [0.5.0] - 2019-02-21

- New `Cookie` class.
- Move `ServerRequest::isSecure` to `Request::isSecure`.
- Adds `Request::getTargetPath`.
- [BC-BREAK] Now `Request` accepts stricter `$uri`.
- [BC-BREAK] Now `ServerRequest` accepts `$_COOKIE` variable before body.

## [0.4.0] - 2019-01-22

- [BC-BREAK] Now `emit` will call the returned function if is not a generator.
- [BC-BREAK] Now `Request` trims and forces headers values to be string.
- [BC-BREAK] Now `Request` throws `InvalidArgumentException` on non-string url.
- [BC-BREAK] Now `Request` filters url as array to be compatible with `parse_url`.
- [BUG] Fix `stream_of` when an empty numeric value is given.
- [BC-BREAK] Now `ServerRequest` throws `LogicException` on cli SAPI.

## [0.3.0] - 2018-10-24

- [BC-BREAK] Now `ServerRequest::$uri['scheme']` is normalized.
- [BC-BREAK] Now `Request::$headers['HOST']` is normalized.
- New `Request::getHost` to retrieve hostname.
- [BC-BREAK] Now `Request::$headers` are normalized to make values as arrays.
- [BC-BREAK] Now `Request` requires host header.
- [BC-BREAK] Now `emit` throws `RuntimeException` if output has already been sent.

## [0.2.0] - 2018-10-19

- [BC-BREAK] Drop PSR-7 support.
- [BC-BREAK] Change versioning scheme to SemVer.

## [0.1.3] - 2016-10-08

- [BUG] Default headers on PSR-7 implementation.
- Adds `Request::isForm`.
- [BC-BREAK] Now `redirect` and `Response::isRedirect` accepts 307 Temporary Redirect.

## [0.1.2] - 2016-08-22

- Adds `redirect` responder.
- [BC-BREAK] Strict types.
- [BC-BREAK] Now `ServerRequest::getUri` doesn't return port if is standard.
- [BC-BREAK] Now `Response` have `Cache-Control` header by default.
  You could override it but it can't be removed.

## [0.1.1] - 2016-08-16

Remove unused functions.

## [0.1.0] - 2016-08-14

Initial version.
