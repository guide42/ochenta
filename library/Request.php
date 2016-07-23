<?php

namespace Ochenta;

/**
 * HTTP/1.1 request implementation.
 */
class Request
{
    private $method;
    private $uri;
    private $headers;
    private $body;

    function __construct(
        string $method,
        string $uri,
        array $headers=[],
        $body=null
    ) {
        $this->method = mb_convert_case($method, MB_CASE_UPPER);
        $this->uri = parse_url($uri);

        if ($this->uri === false) {
            throw new \InvalidArgumentException('Invalid uri');
        }

        $headers = array_change_key_case($headers, CASE_UPPER);
        foreach ($headers as $name => $header) {
            if (!is_array($header)) {
                $headers[$name] = [$header];
            }
        }

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

    /**
     * Retrieves HTTP method.
     *
     * @return string
     */
    function getMethod() {
        return $this->method;
    }

    /**
     * Retrieves original URI parts.
     *
     * @return string[] result of php.net/parse_url function
     */
    function getUri() {
        return $this->uri;
    }

    /**
     * Retrieves the request target.
     *
     * @return string
     */
    function getTarget() {
        return $this->uri['path'] ?? '/';
    }

    /**
     * Retrieves all headers.
     *
     * @return string[][] associative array with each key is the normalizer
     *                    header name, and each value an array of strings for
     *                    that header
     */
    function getHeaders() {
        return $this->headers;
    }

    /**
     * Retrieves body.
     *
     * @return resource|null
     */
    function getBody() {
        return $this->body;
    }
}