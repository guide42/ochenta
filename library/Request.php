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

    /** Content media type or `null` if Content-Type header is not found. */
    private/* ?string */ $mediaType;

    /** Content charset or `null`. */
    private/* ?string */ $charset;

    /** Content media types in the Accept header. */
    private/* ?array */ $acceptsMediaType;

    /** Charsets in the Accept-Charset header. */
    private/* ?array */ $acceptsCharset;

    /** Encodings in the Accept-Encoding header. */
    private/* ?array */ $acceptsEncoding;

    /** Normalized languages in the Accept-Language header. */
    private/* ?array */ $acceptsLanguage;

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

        $this->mediaType = null;
        $this->charset = null;

        $this->acceptsMediaType = null;
        $this->acceptsCharset = null;
        $this->acceptsEncoding = null;
        $this->acceptsLanguage = null;
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
            if ($this->mediaType) {
                return $this->mediaType;
            }
            return $this->mediaType = current(explode(';',
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
            if ($this->charset) {
                return $this->charset;
            }
            $params = array_slice(explode(';',
                strtolower(preg_replace('/\s\s+/', '',
                    current($this->headers['CONTENT-TYPE'])
                ))
            ), 1);
            foreach ($params as $param) {
                $parts = explode('=', trim($param), 2);
                if (current($parts) === 'charset') {
                    return $this->charset = trim(array_pop($parts), '"');
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

    /** True if request is sending JSON content. */
    function isJSON(): bool {
        return preg_match('/[-+\/]json/', $this->getMediaType()) === 1;
    }

    /** True if is an AJAX request. */
    function isAJAX(): bool {
        return isset($this->headers['X-REQUESTED-WITH'])
            && in_array('XMLHttpRequest', $this->headers['X-REQUESTED-WITH']);
    }

    /** Returns parsed Accept header as an associative array with the content
     *  type related with its key-value attributes. */
    function getAcceptMediaType(): ?array {
        if (isset($this->headers['ACCEPT'])) {
            if ($this->acceptsMediaType) {
                return $this->acceptsMediaType;
            }
            return $this->acceptsMediaType = $this->parseAcceptHeader(
                implode(',', $this->headers['ACCEPT'])
            );
        }
        return NULL;
    }

    /** Returns acceptable charsets from parsed Accept-Charset header. */
    function getAcceptCharset(): ?array {
        if (isset($this->headers['ACCEPT-CHARSET'])) {
            if ($this->acceptsCharset) {
                return $this->acceptsCharset;
            }
            return $this->acceptsCharset = $this->parseAcceptHeader(
                implode(',', $this->headers['ACCEPT-CHARSET'])
            );
        }
        return NULL;
    }

    /** Returns acceptable encodings from parsed Accept-Encoding header. */
    function getAcceptEncoding(): ?array {
        if (isset($this->headers['ACCEPT-ENCODING'])) {
            if ($this->acceptsEncoding) {
                return $this->acceptsEncoding;
            }
            return $this->acceptsEncoding = $this->parseAcceptHeader(
                implode(',', $this->headers['ACCEPT-ENCODING'])
            );
        }
        return NULL;
    }

    /** Returns parsed Accept-Language header as an associative array, where
     *  keys are the languages codes in ISO 639.
     */
    function getAcceptLanguage(): ?array {
        if (isset($this->headers['ACCEPT-LANGUAGE'])) {
            if ($this->acceptsLanguage) {
                return $this->acceptsLanguage;
            }
            $languages = array_change_key_case(
                $this->parseAcceptHeader(
                    implode(',', $this->headers['ACCEPT-LANGUAGE'])
                ),
                CASE_UPPER
            );
            foreach ($languages as $lang => $attrs) {
                $codes = explode('-', $lang);
                $codes[0] = strtolower($codes[0]);
                if ($codes[0] == 'i' && count($codes) > 1) {
                    $lang = strtolower($codes[1]);
                } else {
                    $lang = implode('-', $codes);
                }
                $this->acceptsLanguage[$lang] = $attrs;
            }
            return $this->acceptsLanguage;
        }
        return NULL;
    }

    /** Splits header by commas, putting the first element as key of the
     *  returned associative array, then spliting the attributes by semicolon to
     *  form a key-value by spliting a third time by the equal sign. The result
     *  will be sorted by attribute q (quality) if exists.
     */
    private function parseAcceptHeader(string $header): array {
        $parts = array_map(
            function($part) {
                return array_map('trim', explode(';', $part));
            },
            explode(',', $header)
        );
        $accept = [];
        foreach ($parts as $part) {
            $value = array_shift($part);
            $accept[$value] = array();
            foreach ($part as $attribute) {
                list($attrname, $attrvalue) = explode('=', $attribute, 2);
                $accept[$value][$attrname] = trim($attrvalue, '"');
            }
        }
        $index = array_flip(array_keys($accept));
        uksort($accept, function(string $a, string $b) use($accept, $index) {
            $qa = $accept[$a]['q'] ?? 1.0;
            $qb = $accept[$b]['q'] ?? 1.0;
            if ($qa == $qb) {
                return $index[$a] > $index[$b] ? 1 : -1;
            }
            return $qa > $qb ? -1 : 1;
        });
        return $accept;
    }
}