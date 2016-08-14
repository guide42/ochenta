<?php

namespace ochenta\psr7;

use Psr\Http\Message\UriInterface;

/** PSR-7 URI implementation.
  */
class Uri implements UriInterface
{
    /** @var array */
    protected $components = FALSE;

    /** @var array */
    protected $allowedSchemes = [
        'http'  => 80,
        'https' => 443,
    ];

    function __toString() {
        return (($authority = $this->getAuthority()) ?
                ($this->getScheme() ?: 'http') . '://' : '') . $authority .
               (($path = $this->getPath())[0] === '/' ? '' : ':') . $path .
               (($query = $this->getQuery()) ? '?' : '') . $query .
               (($fragment = $this->getFragment()) ? '#' : '') . $fragment;
    }

    function __construct($uri=NULL) {
        if (is_null($uri)) {
            $this->components = [];
        } elseif (is_array($uri)) {
            $this->components = $uri;
        } elseif (is_string($uri)) {
            $this->components = parse_url($uri);
        } elseif ($uri instanceof self) {
            $this->components = $uri->extract();
        } elseif ($uri instanceof UriInterface) {
            $this->components = [
                'scheme' => $uri->getScheme(),
                'user' => strstr($this->getUserInfo(), ':'),
                'pass' => strrchr($this->getUserInfo(), ':'),
                'host' => $uri->getHost(),
                'port' => $uri->getPort(),
                'path' => $uri->getPath(),
                'query' => $uri->getQuery(),
                'fragment' => $uri->getFragment(),
            ];
        }
        if (!isset($this->allowedSchemes[$this->components['scheme'] ?? 'http'])) {
            throw new \InvalidArgumentException('Invalid scheme');
        }
        if (isset($this->components['host'])) {
            $this->components['host'] = strtolower($this->components['host']);
        }
        if (isset($this->components['port'])) {
            $this->components['port'] = (int) $this->components['port'];
        }

        if ($this->components === FALSE) {
            throw new \InvalidArgumentException('Malformed uri');
        }
    }

    function extract(): array {
        return $this->components;
    }

    function extend(callable $fn): self {
        return new self(call_user_func($fn, $this));
    }

    private function isSchemeAllowed() {
        return ($this->allowedSchemes[$this->components['scheme'] ?? ''] ?? 80) ===
               ($this->components['port'] ?? 80);
    }

    function getScheme(): string {
        return $this->components['scheme'] ?? '';
    }

    function getAuthority(): string {
        return rtrim(ltrim(
            $this->getUserInfo() .'@'. $this->getHost() .':'. ($this->getPort() ?: ''), '@'), ':');
    }

    function getUserInfo(): string {
        return trim(($this->components['user'] ?? '') .':'. ($this->components['pass'] ?? ''), ':');
    }

    function getHost(): string {
        return $this->components['host'] ?? '';
    }

    function getPort()/* int|null */ {
        if ($this->isSchemeAllowed()) {
            return NULL;
        }
        return $this->components['port'] ?? NULL;
    }

    function getPath(): string {
        return $this->components['path'] ?? '';
    }

    function getQuery(): string {
        return $this->components['query'] ?? '';
    }

    function getFragment(): string {
        return $this->components['fragment'] ?? '';
    }

    function withScheme(/*string */$scheme): self {
        $uri = $this->components;
        $uri['scheme'] = $scheme;

        return new self($uri);
    }

    function withUserInfo(/*string */$user, $password=NULL): self {
        $uri = $this->components;
        $uri['user'] = $user;
        $uri['pass'] = $password;

        return new self($uri);
    }

    function withHost(/*string */$host): self {
        $uri = $this->components;
        $uri['host'] = $host;

        return new self($uri);
    }

    function withPort($port): self {
        $uri = $this->components;
        $uri['port'] = $port;

        return new self($uri);
    }

    function withPath(/*string */$path): self {
        $uri = $this->components;
        $uri['path'] = $path;

        return new self($uri);
    }

    function withQuery(/*string */$query): self {
        $uri = $this->components;
        $uri['query'] = $query;

        return new self($uri);
    }

    function withFragment(/*string */$fragment): self {
        $uri = $this->components;
        $uri['fragment'] = $fragment;

        return new self($uri);
    }
}