<?php

namespace ochenta\psr7;

use Psr\Http\Message\StreamInterface;

/** PSR-7 stream implementation.
  */
class Stream implements StreamInterface
{
    /** @var resource */
    protected $resource;

    function __toString(): string {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\RuntimeException $e) {
            return '';
        }
    }

    function __construct($resource) {
        if ($resource instanceof Stream) {
            $this->resource = $resource->extract();
        } else {
            $this->resource = $resource;
        }
    }

    function extract() {
        return $this->resource;
    }

    function extend(callable $fn): self {
        return new self(call_user_func($fn, $this));
    }

    function close()/* void*/ {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
        $this->detach();
    }

    function detach()/* resource */ {
        $resource = $this->resource;
        $this->resource = NULL;
        return $resource;
    }

    function getSize()/* int|null */ {
        if (is_resource($this->resource)) {
            return fstat($this->resource)['size'];
        }
    }

    function tell(): int {
        if (!is_resource($this->resource) || ($pos = ftell($this->resource)) === FALSE) {
            throw new \RuntimeException('Could not tell the position');
        }
        return $pos;
    }

    function eof(): bool {
        return !is_resource($this->resource) || feof($this->resource);
    }

    function isSeekable(): bool {
        if (!is_resource($this->resource)) {
            return FALSE;
        }
        return stream_get_meta_data($this->resource)['seekable'];
    }

    function seek(/*int */$offset, /*int */$whence=SEEK_SET)/* void*/ {
        if (!$this->isSeekable() || fseek($this->resource, $offset, $whence) === -1) {
            throw new \RuntimeException('Could not seek');
        }
    }

    function rewind()/* void*/ {
        $this->seek(0);
    }

    function isWritable(): bool {
        if (!is_resource($this->resource)) {
            return FALSE;
        }
        $mode = stream_get_meta_data($this->resource)['mode'];
        return strpos($mode, 'x') !== FALSE ||
               strpos($mode, 'w') !== FALSE ||
               strpos($mode, 'c') !== FALSE ||
               strpos($mode, 'a') !== FALSE ||
               strpos($mode, '+') !== FALSE;
    }

    function write(/*string */$string): int {
        if (!$this->isWritable() || ($written = fwrite($this->resource, $string)) === FALSE) {
            throw new \RuntimeException('Could not write');
        }
        return $written;
    }

    function isReadable(): bool {
        if (!is_resource($this->resource)) {
            return FALSE;
        }
        $mode = stream_get_meta_data($this->resource)['mode'];
        return strpos($mode, 'r') !== FALSE ||
               strpos($mode, '+') !== FALSE;

    }

    function read(/*int */$length): string {
        if (!$this->isReadable() || ($data = fread($this->resource, $length)) === FALSE) {
            throw new \RuntimeException('Could not read');
        }
        return $data;
    }

    function getContents(): string {
        if (!$this->isReadable() || ($contents = stream_get_contents($this->resource)) === FALSE) {
            throw new \RuntimeException('Could not get contents');
        }
        return $contents;
    }

    function getMetadata(/*string */$key=NULL) {
        if (is_resource($this->resource)) {
            $meta = stream_get_meta_data($this->resource);
            if (is_null($key)) {
                return $meta;
            }
            return $meta[$key] ?? NULL;
        }
    }
}