<?php declare(strict_types=1);

namespace ochenta\psr7;

use Psr\Http\Message\UriInterface;

/** HTTP request implementation trait. */
trait RequestTrait
{
    /** @var array<string, string|int> */
    protected $uri;

    /** @var string */
    protected $method;

    /** @var array<string, array> */
    protected $headers = [];

    /** @var array<string, string> */
    protected $headerNames = [];

    /** @var string|null */
    protected $requestTarget;

    function getRequestTarget(): string {
        return $this->requestTarget ?: '';
    }

    function withRequestTarget(/*string */$requestTarget): self {
        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    function withMethod(/*string */$method): self {
        $new = clone $this;
        $new->method = strtoupper($method);

        return $new;
    }

    function getUri(): UriInterface {
        return new Uri($this->uri);
    }

    function withUri(UriInterface $uri, /*bool */$preserveHost=FALSE): self {
        $headers = $this->headers;
        $headers['HOST'] = $headers['HOST'] ?? [];
        if (!$preserveHost && $uri->getHost()) {
            $headers['HOST'] = [$uri->getHost()];
        }

        $new = clone $this;
        $new->headers = $headers;
        $new->headerNames['HOST'] = 'Host';
        $new->uri = parse_url((string) $uri);

        return $new;
    }
}