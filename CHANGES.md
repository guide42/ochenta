# Changes for Ochenta

All notable changes to this project will be documented in this file.

## [Unreleased]

- Drop PSR-7 support.
- Change versioning scheme to SemVer.

## [0.1.3] - 2016-10-08

- Bug with default headers on PSR-7 implementation.
- Adds `Request::isForm`.
- Now `ochenta\redirect` and `Response::isRedirect` accepts 307 Temporary Redirect.

## [0.1.2] - 2016-08-22

- Adds `ochenta\redirect` responder.
- Now `ServerRequest::getUri` doesn't return port if is standard.
- Strict types.
- Now `ochenta\Response` have `Cache-Control` header by default.
  You could override it but it can't be removed.

## [0.1.1] - 2016-08-16

Remove unused functions.

## [0.1.0] - 2016-08-14

Initial version.
