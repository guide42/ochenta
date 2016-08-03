<?php

namespace Ochenta\Psr7;

use Psr\Http\Message\RequestInterface;
use Ochenta\Request as OchentaRequest;

/** HTTP request implementation.
  */
class Request extends OchentaRequest implements RequestInterface
{
    use MessageTrait, RequestTrait;

    function __construct(string $method, $uri, array $headers=[], $body=NULL) {
        foreach ($headers as $name => $header) {
            $this->headerNames[strtoupper($name)] = $name;
        }

        parent::__construct($method, $uri, $headers, $body);
    }

    function getRequestTarget(): string {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }
        return $this->getTarget();
    }
}