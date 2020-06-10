<?php declare(strict_types=1);

namespace ochenta;

/** HTTP/1.1 response implementation. */
class Response {
    /** Status HTTP code. */
    protected/* int */ $statusCode;

    /** Uppercase headers names (as keys) with its list of values. */
    protected/* array */ $headers;

    /** Output body stream or `null` if empty. */
    protected/* ?resource */ $body;

    /** Responses objects contains an HTTP status code, a list of headers which
     *  values should already be normalized and a string or stream body. If any
     *  parameter is invalid {@throws \InvalidArgumentException}.
     */
    function __construct(int $statusCode=200, array $headers=[], /* ?resource */ $body=NULL) {
        if ($statusCode < 100 || $statusCode >= 600) {
            throw new \InvalidArgumentException('Status code must be between 100 and 600');
        }

        foreach ($headers as $name => $header) {
            if (is_scalar($header)) {
                $headers[$name] = [$header];
            } elseif (!is_array($header)) {
                throw new \InvalidArgumentException('Invalid "' . $name . '" header value');
            }
        }

        $headers = array_change_key_case($headers, CASE_UPPER);
        $headers += [ // defaults headers
            'CACHE-CONTROL' => ['no-store', 'no-cache', 'must-revalidate',
                                'post-check=0', 'pre-check=0'],
        ];

        if (($statusCode >= 100 && $statusCode < 200) || in_array($statusCode, [204, 304])) {
            $body = NULL;
            unset(
                $headers['CONTENT-TYPE'], $headers['CONTENT-LENGTH'],
                $headers['CONTENT-ENCODING'], $headers['CONTENT-LANGUAGE'],
                $headers['CONTENT-RANGE'], $headers['CONTENT-MD5']
            );
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

    /** Sets the response in corcondance with the given request. */
    function prepare(Request $req): self {
        $res = clone $this;

        if ($req->getMethod() === 'HEAD') {
            $res->body = NULL;
            unset(
                $res->headers['CONTENT-TYPE'], $res->headers['CONTENT-LENGTH'],
                $res->headers['CONTENT-ENCODING'], $res->headers['CONTENT-LANGUAGE'],
                $res->headers['CONTENT-RANGE'], $res->headers['CONTENT-MD5']
            );
        }

        return $res;
    }

    /** Retrieves status code. */
    function getStatusCode(): int {
        return $this->statusCode;
    }

    /** Returns true if response is a redirection, false otherwise. */
    function isRedirect(): bool {
        return in_array($this->statusCode, [301, 302, 307, 308]);
    }

    /** Returns true if response doesn't need a content, false otherwise. */
    function isEmpty(): bool {
        return in_array($this->statusCode, [204, 304]);
    }

    /** Retrieves headers. @see Ochenta\Request::getHeaders() */
    function getHeaders(): array {
        return $this->headers;
    }

    /** Retrieves body. */
    function getBody()/* ?resource */ {
        return $this->body;
    }
}