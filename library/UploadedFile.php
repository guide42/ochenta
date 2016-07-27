<?php

namespace Ochenta;

/** Value object representing a file uploaded through an HTTP request.
  */
class UploadedFile
{
    protected $tmp;
    protected $size;
    protected $error;

    protected $name;
    protected $type;

    function __construct(
        string $tmp,
        int $size,
        int $error,
        string $clientName=null,
        string $clientType=null
    ) {
        $this->tmp = $tmp;
        $this->size = $size;
        $this->error = $error;

        $this->name = $clientName;
        $this->type = $clientType;
    }

    /** Retrieve temporal filename.
      *
      * @return string
      */
    function getFilename(): string {
        return $this->tmp;
    }

    /** Retrieve file size in bytes.
      *
      * @return int
      */
    function getSize(): int {
        return $this->size;
    }

    /** Return true if there wasn't any error, false otherwise.
      *
      * @return bool
      */
    function isOK(): bool {
        return $this->error === UPLOAD_ERR_OK;
    }

    /** Retrieve the filename sent by the client.
      *
      * @return string|null
      */
    function getClientName() {
        return $this->name;
    }

    /** Retrieve the media type sent by the client.
      *
      * @return string|null
      */
    function getClientType() {
        return $this->type;
    }
}