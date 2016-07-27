<?php

namespace Ochenta;

function resource_for($resource) {
    if (is_null($resource)) {
        return null;
    } elseif (is_scalar($resource)) {
        $stream = fopen('php://temp', 'r+');
        if (!empty($resource)) {
            fwrite($stream, $resource);
            fseek($stream, 0);
        }
        return $stream;
    } elseif (is_resource($resource)) {
        return $resource;
    } else {
        throw new \InvalidArgumentException('Invalid resource');
    }
}
