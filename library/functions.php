<?php

namespace Ochenta;

/** @throws InvalidArgumentException */
function resource_of($resource) {
    if (is_null($resource)) {
        return NULL;
    }

    if (is_scalar($resource)) {
        $stream = fopen('php://temp', 'r+');
        if (!empty($resource)) {
            fwrite($stream, $resource);
            fseek($stream, 0);
        }
        return $stream;
    }

    if (is_resource($resource)) {
        return $resource;
    }

    throw new \InvalidArgumentException('Invalid resource');
}

/** @throws RuntimeException */
function mimetype_of($resource, $filename=NULL) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === FALSE) {
        throw new \RuntimeException('Fileinfo database is not available');
    }

    $mimetype = FALSE;
    if (is_file($filename) && is_readable($filename)) {
        $mimetype = finfo_file($finfo, $filename);
    }

    if ($mimetype === FALSE) {
        $contents = FALSE;

        if (is_string($resource)) {
            $contents = $resource;
        } elseif (is_resource($resource)) {
            $contents = stream_get_contents($resource, -1, 0);
        }

        if ($contents !== FALSE) {
            $mimetype = finfo_buffer($finfo, $contents);
        }
    }
    finfo_close($finfo);

    if ($mimetype === FALSE) {
        throw new \RuntimeException('Couldn\'t detect mime type from resource');
    }

    return $mimetype;
}

/** @throws InvalidArgumentException */
function hash($resource, $algo='md5') {
    if (is_scalar($resource)) {
        return \hash($algo, $resource);
    }

    if (is_resource($resource)) {
        if (!stream_get_meta_data($resource)['seekable']) {
            throw new \InvalidArgumentException('Resource is not hashable (is not seekable)');
        }
        $context = hash_init($algo);
        fseek($resource, 0, SEEK_SET);
        while (!feof($resource)) {
            hash_update($context, fread($resource, 4096));
        }
        return hash_final($context);
    }

    throw new \InvalidArgumentException('Resource is not hashable');
}

/** @throws RuntimeException */
function emit(ServerRequest $req, callable $handler) {
    $generator = $handler($req, function(int $status, array $headers) {
        if (headers_sent()) {
            throw new \RuntimeException('Headers already sent');
        }

        // Root namespace must be explicity declared because the presence
        // of `Ochenta\header` middleware.
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

    try {
        foreach ($generator as $output) {
            echo $output;
        }
    } finally {
        try {
            $close = $generator->getReturn();
        } catch (\Exception $ex) {
            $close = NULL;
        }
        if (is_callable($close)) {
            $close();
        }
    }
}

/** @throws InvalidArgumentException */
function stack(callable $responder, $resolver, ...$stack) {
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
    return array_reduce(array_reverse(iterator_to_array($flatten($stack), false)), $resolver, $responder);
}

function header(string $name, ...$values) {
    return function(callable $handler) use($name, $values): callable {
        return function(ServerRequest $req, callable $open) use($name, $values, $handler) {
            return $handler($req, function(int $status, array $headers) use($name, $values, $open) {
                $headers[$name] = $values;
                $open($status, $headers);
            });
        };
    };
}
