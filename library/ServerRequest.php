<?php declare(strict_types=1);

namespace ochenta;

/** An HTTP request for PHP's SAPI. */
class ServerRequest extends Request {
    /** Parsed query string. */
    protected/* array */ $query;

    /** Form parameters. */
    protected/* array */ $xargs;

    /** Normalized input files. */
    protected/* array */ $files;

    /** Cookie values. */
    protected/* array */ $cookie;

    /** When creating a server request, PHP superglobals will be used in place
     *  of any NULL parameters. This class required that is run in a web SAPI
     *  environment: {@throws \LogicException} when run from the command line.
     */
    function __construct(
        array $server=NULL,
        array $query=NULL,
        array $xargs=NULL,
        array $files=NULL,
        array $cookie=NULL,
        /* ?resource */ $body=NULL
    ) {
        if (empty($server)) {
            $server = $_SERVER + [
                'SERVER_PROTOCOL'      => 'HTTP/1.1', // ignored
                'SERVER_NAME'          => 'localhost',
                'SERVER_PORT'          => 80,

                'SCRIPT_NAME'          => '/index.php',
                'PATH_INFO'            => '/',

                'HTTP_HOST'            => 'localhost',

                'REQUEST_URI'          => '/',
                'REQUEST_METHOD'       => 'GET',
            ];
        }

        if (($server['argc'] ?? 0) > 0) {
            throw new \LogicException('Cannot create server request in a non-server environment');
        }

        $this->query = $query ?: $_GET;
        $this->xargs = $xargs ?: $_POST;
        $this->files = iterator_to_array($this->parseFiles($files ?: $_FILES));
        $this->cookie = $cookie ?: $_COOKIE;

        $server = $this->normalizeServer($server);
        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->normalizeUrl($server['REQUEST_URI'] ?? '/', $server);

        if (empty($this->query) && isset($uri['query'])) {
            parse_str($uri['query'], $this->query);
        }

        $headers = iterator_to_array($this->parseServerHeaders($server));

        if (empty($body) && (isset($headers['CONTENT-LENGTH']) || isset($headers['TRANSFER-ENCODING']))) {
            $body = fopen('php://input', 'rb');
        }

        parent::__construct(
            $method,
            $uri,
            $headers,
            $body
        );
    }

    /** Retrieve query string parameters. */
    function getQuery(): array {
        return $this->query;
    }

    /** Retrieve parameters provided in the request body. */
    function getParsedBody(): array {
        if (empty($this->xargs) &&
            !is_null($this->getBody()) &&
            in_array($this->getMethod(), ['POST']) &&
            in_array($this->getMediaType(), ['application/x-www-form-urlencoded'])
        ) {
            parse_str(stream_get_contents($this->getBody()), $this->xargs);
        }
        return $this->xargs;
    }

    /** Retrieve normalized file uploads. */
    function getFiles(): array {
        return $this->files;
    }

    /** Retrieve cookie values. */
    function getCookie(): array {
        return $this->cookie;
    }

    private $specialHeaders = ['CONTENT_TYPE', 'CONTENT_LENGTH'];
    private $invalidHeaders = ['HTTP_PROXY'];

    /** Generator that will parse the `$_SERVER` superglobal, extract and yield
     *  headers: header name as key and an array of values.
     */
    private function parseServerHeaders(array $server): \Generator {
        foreach ($server as $key => $value) {
            if (strncmp($key, 'HTTP_', 5) === 0 && !in_array($key, $this->invalidHeaders)) {
                yield str_replace('_', '-', substr($key, 5)) => (array) $value;
            } elseif (in_array($key, $this->specialHeaders)) {
                yield str_replace('_', '-', $key) => (array) $value;
            }
        }
    }

    /** Generator that will normalize the `$_FILES` tree to mimic the naming
     *  structure in which files were submitted. When any of the given files
     *  is invalid {@throws \UnexpectedValueException}.
     */
    private function parseFiles(array $files): \Generator {
        foreach ($files as $key => $file) {
            if (!is_array($file) || !isset($file['error'])) {
                throw new \UnexpectedValueException('Invalid uploaded file');
            }

            if (!is_array($file['error'])) {
                yield $key => $file;
            } else {
                $indexed = [];
                foreach ($file['error'] as $index => $_) {
                    $indexed[$index] = [
                        'tmp_name' => $file['tmp_name'][$index],
                        'size'     => $file['size'][$index],
                        'error'    => $file['error'][$index],
                        'name'     => $file['name'][$index],
                        'type'     => $file['type'][$index],
                    ];
                }

                yield $key => iterator_to_array($this->parseFiles($indexed));
            }
        }
    }

    /** Deduce some `$_SERVER` entries from others. */
    private function normalizeServer(array $server): array {
        if (isset($server['SCRIPT_URI'])) {
            $server['HTTPS'] = stripos($server['SCRIPT_URI'], 'https://') === 0 ? 'on' : 'off';
        } elseif (isset($server['HTTPS']) && is_int($server['HTTPS'])) {
            $server['HTTPS'] = $server['HTTPS'] === 1 ? 'on' : 'off';
        } else {
            $server['HTTPS'] = strtolower($server['HTTPS'] ?? 'off');
        }
        if (isset($server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $server['REQUEST_METHOD'] = $server['HTTP_X_HTTP_METHOD_OVERRIDE'];
        }
        if (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
            $server['HTTP_AUTHORIZATION'] = $server['REDIRECT_HTTP_AUTHORIZATION'];
        }
        return $server;
    }

    /** Parses the given `$url` and completes with information from the server
     *  entries. When cannot be parsed {@throws \UnexpectedValueException}.
     */
    private function normalizeUrl(string $url, array $server): array {
        $parts = parse_url($url);
        if ($parts === FALSE) {
            throw new \UnexpectedValueException('Invalid uri');
        }

        if (empty($parts['scheme'])) {
            $parts['scheme'] = $server['HTTPS'] === 'on' ? 'https' : 'http';
        } else {
            $parts['scheme'] = strtolower($parts['scheme']);
        }
        $defaultPort = $parts['scheme'] === 'https' ? 443 : 80;

        if (empty($parts['user']) && isset($server['PHP_AUTH_USER'])) {
            $parts['user'] = $server['PHP_AUTH_USER'];
        }
        if (empty($parts['pass']) && isset($server['PHP_AUTH_PW'])) {
            $parts['pass'] = $server['PHP_AUTH_PW'];
        }
        if (empty($parts['user']) &&
            isset($server['HTTP_AUTHORIZATION']) &&
            stripos($server['HTTP_AUTHORIZATION'], 'basic') === 0
        ) {
            $decoded = base64_decode(substr($server['HTTP_AUTHORIZATION'], 6));
            if (strpos($decoded, ':') !== FALSE) {
                $parts['user'] = strchr($decoded, ':', TRUE);
                $parts['pass'] = substr(strchr($decoded, ':'), 1);
            }
        }

        if (empty($parts['host'])) {
            $parts['host'] = strtolower($server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? 'localhost');
            if (strpos($parts['host'], ':') !== FALSE) {
                list($parts['host'], $port) = explode(':', $parts['host'], 2);
                if (empty($parts['port']) && $port != $defaultPort) {
                    $parts['port'] = (int) $port;
                }
            }
        }
        if (preg_replace('/[a-zA-Z0-9-:\[\]]+\.?/', '', $parts['host']) !== '') {
            throw new \UnexpectedValueException('Invalid host');
        }
        if (empty($parts['port']) && ($server['SERVER_PORT'] ?? $defaultPort) !== $defaultPort) {
            $parts['port'] = (int) $server['SERVER_PORT'];
        }
        if (isset($parts['port']) && $parts['port'] === $defaultPort) {
            unset($parts['port']);
        }

        return $parts;
    }
}