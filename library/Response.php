<?php

namespace Ochenta;

/** HTTP/1.1 response implementation.
  */
class Response
{
    private $statusCode;
    private $headers;
    private $body;

    function __construct(int $statusCode=200, array $headers=[], $body=null) {
        if ($statusCode < 100 || $statusCode >= 600) {
            throw new \InvalidArgumentException('Status code must be between 100 and 600');
        }

        foreach ($headers as $name => $header) {
            if (is_scalar($header)) {
                $headers[$name] = [$header];
            } elseif (!is_array($header)) {
                throw new \InvalidArgumentException('Invalid header ' . $name);
            }
        }

        $headers = array_change_key_case($headers, CASE_UPPER);

        if (($statusCode >= 100 && $statusCode < 200) || in_array($statusCode, [204, 304])) {
            unset($headers['CONTENT-TYPE']);
            unset($headers['CONTENT-LENGTH']);
            $body = null;
        } elseif (!isset($headers['CONTENT-TYPE'])) {
            $headers['CONTENT-TYPE'] = ['text/html;charset=utf-8'];
        } elseif (
            stripos($headers['CONTENT-TYPE'][0], 'text/') === 0 &&
            stripos($headers['CONTENT-TYPE'][0], 'charset') === false
        ) {
            $headers['CONTENT-TYPE'][0] .= ';charset=utf-8';
        }

        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    /** Sets the response in corcondance with the given request.
      *
      * @return self
      */
    function prepare(Request $req): self {
        $res = clone $this;

        if ($req->getMethod() === 'HEAD') {
            $res->body = null;
        }

        return $res;
    }

    /** Retrieves status code.
      *
      * @return int
      */
    function getStatusCode(): int {
        return $this->statusCode;
    }

    /** Returns true if response is a redirection, false otherwise.
      *
      * @return bool
      */
    function isRedirect(): bool {
        return in_array($this->statusCode, [301, 302]);
    }

    /** Retrieves headers.
      *
      * @see Ochenta\Request::getHeaders()
      * @return string[][] associative array
      */
    function getHeaders(): array {
        return $this->headers;
    }

    /** Retrieves body.
      *
      * @return resource|null
      */
    function getBody() {
        return $this->body;
    }
}