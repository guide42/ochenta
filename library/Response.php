<?php declare(strict_types=1);

namespace ochenta;

/** HTTP/1.1 response implementation.
  */
class Response
{
    protected $statusCode;
    protected $headers;
    protected $body;

    function __construct(int $statusCode=200, array $headers=[], $body=NULL) {
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
            $body = NULL;
        } elseif (!isset($headers['CONTENT-TYPE'])) {
            $headers['CONTENT-TYPE'] = ['text/html; charset=utf-8'];
        } elseif (
            stripos($headers['CONTENT-TYPE'][0], 'text/') === 0 &&
            stripos($headers['CONTENT-TYPE'][0], 'charset') === FALSE
        ) {
            $headers['CONTENT-TYPE'][0] .= '; charset=utf-8';
        }

        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = stream_of($body);
    }

    /** Sets the response in corcondance with the given request.
      */
    function prepare(Request $req): self {
        $res = clone $this;

        if ($req->getMethod() === 'HEAD') {
            $res->body = null;
        }

        return $res;
    }

    /** Retrieves status code.
      */
    function getStatusCode()/* int*//* is not type-hinted because overlaps with Psr7 */ {
        return $this->statusCode;
    }

    /** Returns true if response is a redirection, false otherwise.
      */
    function isRedirect(): bool {
        return in_array($this->statusCode, [301, 302]);
    }

    /** Retrieves headers. @see Ochenta\Request::getHeaders()
      */
    function getHeaders(): array/* string[][] */ {
        return $this->headers;
    }

    /** Retrieves body.
      */
    function getBody()/* resource|null */ {
        return $this->body;
    }
}