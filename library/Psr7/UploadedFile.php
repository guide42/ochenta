<?php

namespace Ochenta\Psr7;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Ochenta\UploadedFile as OchentaUploadedFile;

/** PSR-7 uploaded file implementation.
  */
class UploadedFile extends OchentaUploadedFile implements UploadedFileInterface
{
    protected $error;
    protected $moved = false;

    function getStream(): StreamInterface {
        if ($this->error !== UPLOAD_ERR_OK || $this->moved) {
            throw new \RuntimeException('No stream can be created');
        }
        return new Stream(fopen($this->tmp, 'r+'));
    }

    function moveTo(/*string */$targetPath)/*: void*/ {
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

    function getError(): int {
        return $this->error;
    }

    function getClientFilename() {
        return $this->getClientName();
    }

    function getClientMediaType() {
        return $this->getClientType();
    }
}