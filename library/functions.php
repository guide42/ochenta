<?php declare(strict_types=1);

namespace ochenta;

/** Converts the given resource into a response. Input can be an instance
 *  of `Response` that will be prepared before emitted, any resource will
 *  be readed, scalars emitted as-is or {@throws \InvalidArgumentException}
 *  on any other value.
 */
function responder_of($resource): callable {
    if ($resource instanceof Response) {
        return function(ServerRequest $req, callable $open) use($resource) {
            $res = $resource->prepare($req);
            $open($res->getStatusCode(), $res->getHeaders());
            return responder_of($res->getBody())($req, $open);
        };
    }

    if (is_resource($resource)) {
        return function(ServerRequest $req, callable $open) use($resource) {
            try {
                while (!feof($resource)) {
                    yield fread($resource, 4096);
                }
            } finally {
                fclose($resource);
            }
        };
    }

    if (is_scalar($resource)) {
        return function(ServerRequest $req, callable $open) use($resource) {
            yield $resource;
        };
    }

    if (is_null($resource)) {
        return function(ServerRequest $req, callable $open) {
            yield '';
        };
    }

    if ($resource instanceof \Generator) {
        return function(ServerRequest $req, callable $open) use($resource) {
            return $resource;
        };
    }

    throw new \InvalidArgumentException('Resource cannot be converted to responder');
}

/** Calls given handler with given server request and a open callable that
 *  sends the headers. If buffer is already open or headers are already sent
 *  {@throws RuntimeException}. Iterates over the result of the handler and
 *  prints it out. Flush function can be replaced returning a callable in the
 *  handler.
 */
function emit(ServerRequest $req, callable $handler): void {
    $res = $handler($req, function(int $status, array $headers) {
        if (headers_sent()) {
            throw new \RuntimeException('Headers already sent');
        }

        // Root namespace must be explicity declared because the presence
        // of `ochenta\header` middleware.
        \header(sprintf('HTTP/1.1 %d', $status));

        foreach ($headers as $name => $values) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            $first = TRUE;
            foreach ($values as $value) {
                \header("$name: $value", $first);
                $first = FALSE;
            }
        }
    });

    if (ob_get_level() > 0 && ob_get_length() > 0) {
        throw new \RuntimeException('Output already sent');
    }

    if (is_iterable($res)) {
        foreach ($res as $output) {
            echo $output;
        }
        if ($res instanceof \Generator) {
            $res = $res->getReturn();
        }
    }

    (is_callable($res) ? $res : 'flush')();
}

/** Returns a callable that is last of the stack, optionally with a resolver
 *  callable or {@throws InvalidArgumentException} when any argument is not
 *  valid: values of the stack (or middlewares) must be callables and accept
 *  previous value as argument, resolver must be also callables and at least
 *  one middleware in the stack. */
function stack(callable $initial, $resolver, ...$stack): callable {
    if (is_array($resolver)) {
        $stack += $resolver;
        $resolver = function(callable $prev, $handler) {
            if (is_callable($handler)) {
                return $handler($prev);
            }
            throw new \InvalidArgumentException('Invalid middleware');
        };
    } elseif (!is_callable($resolver)) {
        throw new \InvalidArgumentException('Resolver must be a callable');
    }

    if (empty($stack)) {
        throw new \InvalidArgumentException('At least one middleware is required');
    }

    $flatten = function(array $list) use(&$flatten) {
        foreach ($list as $item) {
            if (is_array($item)) {
                yield from $flatten($item);
            } else {
                yield $item;
            }
        }
    };
    return array_reduce(array_reverse(iterator_to_array($flatten($stack), FALSE)), $resolver, $initial);
}

/** Returns a middleware that appends a header with given values.
 *  Intercepts open function.
 */
function header(string $name, ...$values): callable {
    return function(callable $handler) use($name, $values): callable {
        return function(ServerRequest $req, callable $open) use($name, $values, $handler) {
            return $handler($req, function(int $status, array $headers) use($name, $values, $open) {
                $headers[$name] = $values;
                $open($status, $headers);
            });
        };
    };
}

/** Returns a middleware that prepends html content before the closing tag. */
function append(string $content, string $tag='body'): callable {
    return function(callable $handler) use($content, $tag): callable {
        return function(ServerRequest $req, callable $open) use($content, $tag, $handler) {
            $res = $handler($req, $open);

            foreach ($res as $output) {
                if (($pos = stripos($output, "</$tag>")) !== FALSE) {
                    yield substr($output, 0, $pos);
                    yield $content;
                    yield substr($output, $pos);
                } else {
                    yield $output;
                }
            }

            return $res->getReturn();
        };
    };
}

/** Returns responder that will add the Location header to the given url with
 *  optionally another status code than 302 found. Giving invalid values for
 *  both arguments {@throws InvalidArgumentException}.
 */
function redirect($uri, int $statusCode=302): callable {
    if (!is_array($uri) && ($uri = parse_url($uri)) === FALSE) {
        throw new \InvalidArgumentException('Invalid uri');
    }
    if (!in_array($statusCode, [301, 302, 307])) {
        throw new \InvalidArgumentException('Invalid status code');
    }

    return function(ServerRequest $req, callable $open) use($uri, $statusCode) {
        if (empty($uri['scheme'])) {
            $old = $req->getUri();
            $uri['scheme'] = $old['scheme'];
            $uri['host'] = $old['host'];
            if (isset($old['port'])) {
                $uri['port'] = $old['port'];
            }
        }

        $url = $uri['scheme'] . '://' . $uri['host']
             . (isset($uri['port']) ? ':' . $uri['port'] : '') . ($uri['path'] ?? '/')
             . (isset($uri['query']) ? '?' . $uri['query'] : '');

        $open($statusCode, [
            'Location' => [$url],
        ]);
    };
}

/** Returns stream resource or null.
 *  Invalid values {@throws InvalidArgumentException}.
 */
function stream_of($resource)/* ?resource */ {
    if (is_null($resource)) {
        return NULL;
    }

    if ($resource instanceof Request || $resource instanceof Response) {
        $resource = $resource->getBody();
    }

    if (is_resource($resource)) {
        return $resource;
    }

    if (is_scalar($resource)) {
        /** @var resource $stream */
        $stream = fopen('php://temp', 'r+');

        if ($resource !== '') {
            fwrite($stream, strval($resource));
            fseek($stream, 0);
        }

        return $stream;
    }

    throw new \InvalidArgumentException('Invalid resource type ' . gettype($resource));
}
