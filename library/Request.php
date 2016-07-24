<?php

namespace Ochenta;

/** HTTP/1.1 request implementation.
  */
class Request
{
    private $method;
    private $uri;
    private $headers;
    private $body;

    function __construct(string $method, $uri, array $headers=[], $body=null) {
        $this->method = strtoupper($method);
        $this->uri = is_array($uri) ? $uri : parse_url($uri);

        if ($this->uri === false) {
            throw new \InvalidArgumentException('Invalid uri');
        }

        $headers = array_change_key_case($headers, CASE_UPPER);

        if (isset($this->uri['host']) && !isset($headers['HOST'])) {
            $headers = ['HOST' => [$this->uri['host']]] + $headers;
        }

        if (is_null($body)) {
            $stream = null;
        } elseif (is_scalar($body)) {
            $stream = fopen('php://temp', 'r+');
            if (!empty($body)) {
                fwrite($stream, $body);
                fseek($stream, 0);
            }
        } elseif (is_resource($body)) {
            $stream = $body;
        } else {
            throw new \InvalidArgumentException('Invalid body');
        }

        $this->headers = $headers;
        $this->body = $stream;
    }

    /** Retrieves HTTP method.
      *
      * @return string
      */
    function getMethod(): string {
        return $this->method;
    }

    /** Retrieves original URI parts.
      *
      * @return string[] result of php.net/parse_url function
      */
    function getUri(): array {
        return $this->uri;
    }

    /** Retrieves the request target.
      *
      * @return string
      */
    function getTarget(): string {
        return $this->uri['path'] ?? '/';
    }

    /** Retrieves all headers.
      *
      * @return string[][] associative array with each key is the normalizer
      *                    header name, and each value an array of strings for
      *                    that header
      */
    function getHeaders(): array {
        return $this->headers;
    }

    /** Returns normalized content type without parameters.
      *
      * @return string|null
      */
    function getMediaType() {
        if (isset($this->headers['CONTENT-TYPE'])) {
            return current(explode(';', strtolower(preg_replace('/\s\s+/', '',
                   current($this->headers['CONTENT-TYPE'])))));
        }
    }

    /** Returns normalized content type charset.
      *
      * @return string|null
      */
    function getCharset() {
        if (isset($this->headers['CONTENT-TYPE'])) {
            $params = array_slice(explode(';', strtolower(preg_replace('/\s\s+/', '',
                      current($this->headers['CONTENT-TYPE'])))), 1);
            foreach ($params as $param) {
                $parts = explode('=', $param, 2);
                if (current($parts) === 'charset') {
                    return trim(array_pop($parts), '"');
                }
            }
        }
    }

    /** Retrieves body.
      *
      * @return resource|null
      */
    function getBody() {
        return $this->body;
    }
}