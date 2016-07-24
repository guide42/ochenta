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
            throw new \InvalidArgumentException(
                'Status code must be between 100 and 600');
        }

        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    /** Retrieves status code.
      *
      * @return int
      */
    function getStatusCode(): int {
        return $this->statusCode;
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