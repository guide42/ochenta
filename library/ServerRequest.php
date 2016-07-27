<?php

namespace Ochenta;

/** An HTTP request for PHP's SAPI.
  */
class ServerRequest extends Request
{
    protected $query;
    protected $xargs;
    protected $files;

    /** @throws UnexpectedValueException */
    function __construct(
        array $server=null,
        array $query=null,
        array $xargs=null,
        array $files=null
    ) {
        if (empty($server)) {
          $server = $_SERVER;
        }

        $this->query = $query ?: $_GET;
        $this->xargs = $xargs ?: $_POST;
        $this->files = iterator_to_array($this->parseFiles($files ?: $_FILES));

        $server = $this->normalizeServer($server);
        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->normalizeUrl($server['REQUEST_URI'] ?? '/', $server);

        if (empty($this->query) && isset($uri['query'])) {
            parse_str($uri['query'], $this->query);
        }

        $headers = iterator_to_array($this->parseServerHeaders($server));
        $body = null;

        if (isset($headers['CONTENT-LENGTH']) || isset($headers['TRANSFER-ENCODING'])) {
            $body = fopen('php://input', 'rb');
        }

        parent::__construct(
          $method,
          $uri,
          $headers,
          $body
        );
    }

    /** Retrieve query string parameters.
      *
      * @return string[]
      */
    function getQuery(): array {
        return $this->query;
    }

    /** Retrieve parameters provided in the request body.
      *
      * @return string[]
      */
    function getParsedBody(): array {
        if (empty($thix->xargs) &&
            !is_null($this->getBody()) &&
            in_array($this->getMethod(), ['POST']) &&
            in_array($this->getMediaType(), ['application/x-www-form-urlencoded'])
        ) {
            parse_str(stream_get_contents($this->getBody()), $this->xargs);
        }
        return $this->xargs;
    }

    /** Retrieve normalized file uploads.
      *
      * @return array[]
      */
    function getFiles(): array {
        return $this->files;
    }

    private $specialHeaders = ['CONTENT_TYPE', 'CONTENT_LENGTH'];
    private $invalidHeaders = ['HTTP_PROXY'];

    private function parseServerHeaders(array $server) {
        foreach ($server as $key => $value) {
            if (strncmp($key, 'HTTP_', 5) === 0 &&
                !in_array($key, $this->invalidHeaders)
            ) {
                yield str_replace('_', '-', substr($key, 5)) => (array) $value;
            } elseif (in_array($key, $this->specialHeaders)) {
                yield str_replace('_', '-', $key) => (array) $value;
            }
        }
    }

    private function parseFiles(array $files) {
        foreach ($files as $key => $file) {
            if (!is_array($file)) {
                throw new \UnexpectedValueException('Invalid uploaded file');
            }

            if (!isset($file['error'])) {
                yield $key => iterator_to_array($this->parseFiles($file));
            } elseif (!is_array($file['error'])) {
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

    private function normalizeServer(array $server): array {
        if (isset($server['SCRIPT_URI'])) {
            $server['HTTPS'] = strpos($server['SCRIPT_URI'], 'https://') === 0 ? 'on' : 'off';
        }
        if (isset($server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $server['REQUEST_METHOD'] = $server['HTTP_X_HTTP_METHOD_OVERRIDE'];
        }
        return $server;
    }

    private function normalizeUrl(string $url, array $server): array {
        $parts = parse_url($url);
        if ($parts === false) {
            throw new \UnexpectedValueException('Invalid uri');
        }

        if (empty($parts['scheme'])) {
            $parts['scheme'] = ($server['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http';
        }
        $defaultPort = $parts['scheme'] === 'https' ? 443 : 80;
        if (empty($parts['user']) && isset($server['PHP_AUTH_USER'])) {
            $parts['user'] = $server['PHP_AUTH_USER'];
        }
        if (empty($parts['pass']) && isset($server['PHP_AUTH_PW'])) {
            $parts['pass'] = $server['PHP_AUTH_PW'];
        }
        if (empty($parts['host'])) {
            $parts['host'] = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? 'localhost';
            if (strpos($parts['host'], ':') !== false) {
                list($host, $port) = explode(':', $parts['host'], 2);
                $parts['host'] = $host;
                if (empty($parts['port']) && $port != $defaultPort) {
                    $parts['port'] = (int) $port;
                }
            }
        }
        $parts['host'] = strtolower($parts['host']);
        if (!empty($parts['host']) &&
            preg_replace('/[a-zA-Z0-9-:\[\]]+\.?/', '', $parts['host']) !== ''
        ) {
            throw new \UnexpectedValueException('Invalid host');
        }
        if (empty($parts['port']) && ($server['SERVER_PORT'] ?? $defaultPort) !== $defaultPort) {
            $parts['port'] = (int) $server['SERVER_PORT'];
        }

        return $parts;
    }
}