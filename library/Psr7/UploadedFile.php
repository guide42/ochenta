<?php

namespace Ochenta\Psr7;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/** PSR-7 uploaded file implementation.
  */
class UploadedFile implements UploadedFileInterface
{
    protected $tmp;
    protected $size;
    protected $error;

    protected $name;
    protected $type;

    protected $moved = false;

    function __construct(
        string $tmp,
        int $size,
        int $error,
        string $clientName=NULL,
        string $clientType=NULL
    ) {
        $this->tmp = $tmp;
        $this->size = $size;
        $this->error = $error;

        $this->name = $clientName;
        $this->type = $clientType;
    }

    function getStream(): StreamInterface {
        if ($this->error !== UPLOAD_ERR_OK || $this->moved) {
            throw new \RuntimeException('No stream can be created');
        }
        return new Stream(fopen($this->tmp, 'r+'));
    }

    function moveTo(/*string */$targetPath)/* void*/ {
        if (empty($targetPath)) {
            throw new \InvalidArgumentException('Invalid target path');
        }
        if ($this->error !== UPLOAD_ERR_OK || $this->moved) {
            throw new \RuntimeException('No stream can be created');
        }
        if (PHP_SAPI === 'cli') {
            $this->moved = rename($this->tmp, $targetPath);
        } elseif (is_uploaded_file($this->tmp)) {
            $this->moved = move_uploaded_file($this->tmp, $targetPath);
        }
        if ($this->moved === false) {
            throw new \RuntimeException('Couldn\'t move file');
        }
    }

    function getSize(): int {
        return $this->size;
    }

    function getError(): int {
        return $this->error;
    }

    function getClientFilename()/* string|null */ {
        return $this->name;
    }

    function getClientMediaType()/* string|null */ {
        return $this->type;
    }
}