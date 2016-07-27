<?php

namespace Ochenta\Psr7;

use Psr\Http\Message\ServerRequestInterface;
use Ochenta\ServerRequest as OchentaServerRequest;

/** HTTP request implementation.
  */
class ServerRequest extends OchentaServerRequest implements ServerRequestInterface
{
    use MessageTrait, RequestTrait;

    protected $query;
    protected $xargs;
    protected $files;

    protected $server;
    protected $cookie;
    protected $uploadedFiles = [];
    protected $attributes = [];

    function __construct(
        array $server=null,
        array $query=null,
        array $xargs=null,
        array $files=null,
        array $cookie=null
    ) {
        $this->server = $server ?: $_SERVER;
        $this->cookie = $cookie ?: $_COOKIE;

        parent::__construct($server, $query, $xargs, $files);

        foreach ($this->headers as $name => $header) {
            $this->headerNames[$name] = str_replace(' ', '-', ucwords(
                                        str_replace('-', ' ', strtolower($name))));
        }
    }

    function getRequestTarget(): string {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }
        return $this->getTarget();
    }

    function getServerParams(): array {
        return $this->server;
    }

    function getCookieParams(): array {
        return $this->cookie;
    }

    function withCookieParams(array $cookies): self {
        $new = clone $this;
        $new->cookie = $cookies;

        return $new;
    }

    function getQueryParams(): array {
        return $this->query;
    }

    function withQueryParams(array $query): self {
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    private function parseUploadedFiles(array $files) {
        foreach ($files as $key => $file) {
            if (!isset($file['error'])) {
                yield $key => iterator_to_array($this->parseUploadedFiles($file));
            } else {
                yield $key => new UploadedFile(
                    $file['tmp_name'],
                    $file['size'],
                    $file['error'],
                    $file['name'],
                    $file['type']
                );
            }
        }
    }

    function getUploadedFiles(): array {
        if (empty($this->uploadedFiles)) {
            $this->uploadedFiles = iterator_to_array($this->parseUploadedFiles($this->files));
        }
        return $this->uploadedFiles;
    }

    function withUploadedFiles(array $uploadedFiles): self {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    function withParsedBody($data): self {
        $new = clone $this;
        $new->xargs = $data;

        return $new;
    }

    function getAttributes(): array {
        return $this->attributes;
    }

    function getAttribute($name, $default=null) {
        return $this->attributes[$name] ?? $default;
    }

    function withAttribute($name, $value): self {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    function withoutAttribute($name): self {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }
}