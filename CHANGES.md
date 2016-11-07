# Changes for Ochenta

All notable changes to this project will be documented in this file.
This project adheres to [Fear-Driven Versioning](https://github.com/jonathanong/ferver).

## [Unreleased]

- Bug with default headers on PSR-7 implementation.
- Adds `Request::isForm`.

## [1.2] - 2016-08-22

- Adds `ochenta\redirect` responder.
- Now `ServerRequest::getUri` doesn't return port if is standard.
- Strict types.
- Now `ochenta\Response` have `Cache-Control` header by default.
  You could override it but it can't be removed.

## [1.1] - 2016-08-16

Remove unused functions.

## [1.0] - 2016-08-14

Initial version.
