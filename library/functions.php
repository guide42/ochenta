<?php

namespace ochenta;

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

/** @throws InvalidArgumentException */
function responder_of($resource) {
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

/** @throws RuntimeException */
function emit(ServerRequest $req, callable $handler) {
    $res = $handler($req, function(int $status, array $headers) {
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
        foreach ($res as $output) {
            echo $output;
        }
    } finally {
        try {
            $close = $res->getReturn();
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

function append(string $content, string $tag='body') {
    return function(callable $handler) use($content, $tag): callable {
        return function(ServerRequest $req, callable $open) use($content, $tag, $handler) {
            $res = $handler($req, $open);

            foreach ($res as $output) {
                if (($pos = stripos($output, "</$tag>")) !== false) {
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

/** @throws InvalidArgumentException */
function escape($raw, $type='html', $encoding=null): string {
    if (empty($raw) || ctype_digit($raw) || is_int($raw)) {
        return (string) $raw;
    }

    if ($type === 'html') {
        return htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, $encoding);
    }

    if (strtoupper($encoding) === 'UTF-8' && preg_match('/^./su', $raw)) {
        $str = $raw;
    } elseif (function_exists('iconv')) {
        $str = (string) iconv($encoding, 'UTF-8', $raw);
    } elseif (function_exists('mb_convert_encoding')) {
        $str = (string) mb_convert_encoding($raw, 'UTF-8', $encoding);
    } else {
        throw new \InvalidArgumentException('Invalid encoding');
    }

    $replace = function(string $pattern): callable {
        return function(array $matches) use($pattern): string {
            return sprintf($pattern, ord($matches[0]));
        };
    };

    switch ($type) {
        case 'css': return preg_replace_callback('/[^a-z0-9_]/iSu', $replace('\\%X '), $str);
        case 'js': return preg_replace_callback('/[^a-z0-9_,\.]/iSu', $replace('\\x%02X'), $str);
    }

    throw new \InvalidArgumentException('Invalid type');
}
