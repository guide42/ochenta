<?php declare(strict_types=1);

namespace ochenta;

/** HTTP/1.1 request implementation. */
class Request {
    /** Uppercase HTTP method. */
    protected/* string */ $method;

    /** Parts of the original URI. */
    protected/* array */ $uri;

    /** Uppercase headers names (as keys) with its list of values. */
    protected/* array */ $headers;

    /** Input content stream or `null` if empty. */
    protected/* ?resource */ $body;

    /** Requests are consisted of a method that will be stored in upper case,
     *  an url that can be given in a variety of formats and if cannot be
     *  parsed {@throws \InvalidArgumentException}, a dictionary of headers and
     *  a string or stream body.
     */
    function __construct(string $method, $uri, array $headers=[], /* ?resource */ $body=NULL) {
        $this->method = strtoupper($method);

        if (is_array($uri)) {
            $this->uri = array_filter($uri,
                function($k) {
                    return in_array($k, ['scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment'], TRUE);
                },
                ARRAY_FILTER_USE_KEY
            );
        } elseif (is_string($uri)) {
            $this->uri = parse_url((string) $uri);
        }

        if (empty($this->uri)) {
            throw new \InvalidArgumentException('Invalid uri');
        }

        $headers = array_change_key_case($headers, CASE_UPPER);
        $headers = array_map(
            function($v) {
                return is_array($v) ? array_map('trim', $v) : (array) trim((string) $v);
            },
            $headers
        );

        if (isset($this->uri['host']) && !isset($headers['HOST'])) {
            $headers = ['HOST' => [$this->uri['host']]] + $headers;
        }

        if (isset($headers['HOST'])) {
            $headers['HOST'] = array_map('strtolower', $headers['HOST']);
        } else {
            throw new \InvalidArgumentException('Missing host header');
        }

        $this->headers = $headers;
        $this->body = stream_of($body);
    }

    /** Retrieves HTTP method. */
    function getMethod(): string {
        return $this->method;
    }

    /** Verify that the HTTP method matches a given string. */
    function isMethod(string $method): bool {
        return $this->method === strtoupper($method);
    }

    /** Retrieves original URI parts. */
    function getUri(): array/* result of php.net/parse_url function */ {
        return $this->uri;
    }

    /** Returns true if the request is HTTPS, false otherwise. */
    function isSecure(): bool {
        return ($this->uri['scheme'] ?? 'http') === 'https';
    }

    /** Retrieves request host name. */
    function getHost(): string {
        return current($this->headers['HOST']);
    }

    /** Retrieves request target path. */
    function getTargetPath(): string {
        return $this->uri['path'] ?? '/';
    }

    /** Retrieves request target. */
    function getTarget(): string {
        return rtrim($this->getTargetPath() . '?' . ($this->uri['query'] ?? ''), '?');
    }

    /** Retrieves all headers. */
    function getHeaders(): array/* associative array with each key as the normalized
                                 * header name, and an array of strings values for
                                 * that header */ {
        return $this->headers;
    }

    /** Returns normalized content type without parameters. */
    function getMediaType(): ?string {
        if (isset($this->headers['CONTENT-TYPE'])) {
            return current(explode(';',
                strtolower(preg_replace('/\s\s+/', '',
                    current($this->headers['CONTENT-TYPE'])
                ))
            ));
        }
        return NULL;
    }

    /** Returns normalized content type charset. */
    function getCharset(): ?string {
        if (isset($this->headers['CONTENT-TYPE'])) {
            $params = array_slice(explode(';',
                strtolower(preg_replace('/\s\s+/', '',
                    current($this->headers['CONTENT-TYPE'])
                ))
            ), 1);
            foreach ($params as $param) {
                $parts = explode('=', trim($param), 2);
                if (current($parts) === 'charset') {
                    return trim(array_pop($parts), '"');
                }
            }
        }
        return NULL;
    }

    /** Retrieves body. */
    function getBody()/* ?resource */ {
        return $this->body;
    }

    /** True if the request is submiting a form. */
    function isForm(): bool {
        return in_array($this->getMediaType(), [
            'application/x-www-form-urlencoded',
            'multipart/form-data',
        ]);
    }

    /** True if is an AJAX request. */
    function isAJAX(): bool {
        return isset($this->headers['X-REQUESTED-WITH'])
            && in_array('XMLHttpRequest', $this->headers['X-REQUESTED-WITH']);
    }
}