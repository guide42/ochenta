<?php

namespace Ochenta;

/** @throws InvalidArgumentException */
function resource_of($resource) {
    if (is_null($resource)) {
        return null;
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
function mimetype_of($resource, $filename=null) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        throw new \RuntimeException('Fileinfo database is not available');
    }

    $mimetype = false;
    if (is_file($filename) && is_readable($filename)) {
        $mimetype = finfo_file($finfo, $filename);
    }

    if ($mimetype === false) {
        $contents = false;

        if (is_string($resource)) {
            $contents = $resource;
        } elseif (is_resource($resource)) {
            $contents = stream_get_contents($resource, -1, 0);
        }

        if ($contents !== false) {
            $mimetype = finfo_buffer($finfo, $contents);
        }
    }
    finfo_close($finfo);

    if ($mimetype === false) {
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
