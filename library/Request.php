<?php

namespace Ochenta;

/** HTTP/1.1 request implementation.
  */
class Request
{
    protected $method;
    protected $uri;
    protected $headers;
    protected $body;

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

        $this->headers = $headers;
        $this->body = resource_of($body);
    }

    /** Retrieves HTTP method.
      */
    function getMethod(): string {
        return $this->method;
    }

    /** Retrieves original URI parts.
      */
    function getUri()/* string[] result of php.net/parse_url function *//* is not type-hinted
                      *          because overlaps with Psr7 */ {
        return $this->uri;
    }

    /** Retrieves the request target.
      */
    function getTarget(): string {
        return rtrim(($this->uri['path'] ?? '/') . '?' . ($this->uri['query'] ?? ''), '?');
    }

    /** Retrieves all headers.
      */
    function getHeaders(): array/* string[][] associative array with each key is the normalizer
                                 *            header name, and each value an array of strings for
                                 *            that header. */ {
        return $this->headers;
    }

    /** Returns normalized content type without parameters.
      */
    function getMediaType()/* string|null */ {
        if (isset($this->headers['CONTENT-TYPE'])) {
            return current(explode(';', strtolower(preg_replace('/\s\s+/', '',
                   current($this->headers['CONTENT-TYPE'])))));
        }
    }

    /** Returns normalized content type charset.
      */
    function getCharset()/* string|null */ {
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
      */
    function getBody()/* resource|null */ {
        return $this->body;
    }
}