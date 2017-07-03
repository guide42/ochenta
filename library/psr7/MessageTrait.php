<?php declare(strict_types=1);

namespace ochenta\psr7;

use Psr\Http\Message\StreamInterface;

/** HTTP message implementation trait. */
trait MessageTrait
{
    protected $headerNames = [];
    protected $headers = [];
    protected $stream;
    protected $body;

    function getProtocolVersion(): string {
        return '1.1';
    }

    /** @deprecated */
    function withProtocolVersion(/*string */$version): self {
        throw new \BadMethodCallException(__CLASS__ . '::withProtocolVersion is not supported');
    }

    function getHeaders(): array {
        $headers = [];
        foreach ($this->headers as $norm => $values) {
            $headers[$this->headerNames[$norm]] = $values;
        }
        return $headers;
    }

    function hasHeader(/*string */$name): bool {
        return isset($this->headers[strtoupper($name)]) &&
               !empty($this->headers[strtoupper($name)]);
    }

    function getHeader(/*string */$name): array {
        return $this->headers[strtoupper($name)] ?? [];
    }

    function getHeaderLine(/*string */$name): string {
        return implode(', ', $this->getHeader($name));
    }

    function withHeader(/*string */$name, $value): self {
        $norm = strtoupper($name);
        $new = clone $this;
        $new->headerNames[$norm] = strval($name);
        $new->headers[$norm] = (array) $value;

        return $new;
    }

    function withAddedHeader(/*string */$name, $value): self {
        $norm = strtoupper($name);
        $new = clone $this;
        $new->headerNames[$norm] = strval($name);
        $new->headers[$norm] = array_merge_recursive((array) $value, $this->headers[$norm] ?? []);

        return $new;
    }

    function withoutHeader(/*string */$name): self {
        $norm = strtoupper($name);
        $new = clone $this;

        unset($new->headerNames[$norm]);
        unset($new->headers[$norm]);

        return $new;
    }

    function getBody(): StreamInterface {
        if (!$this->stream) {
            $this->stream = new Stream($this->body);
        }

        return $this->stream;
    }

    function withBody(StreamInterface $body): self {
        if ($this->stream === $body) {
            return $this;
        }

        $new = clone $this;
        $new->stream = $body;
        $new->body = $body->detach();

        return $new;
    }
}