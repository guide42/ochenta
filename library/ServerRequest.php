<?php

namespace Ochenta;

/**
 * An HTTP request for PHP's SAPI.
 */
class ServerRequest extends Request
{
    private $query;
    private $xargs;
    private $files;

    function __construct(
        array $server=null,
        array $query=null,
        array $xargs=null,
        array $files=null
    ) {
        $this->query = $query ?: $_GET;
        $this->xargs = $xargs ?: $_POST;
        $this->files = iterator_to_array($this->parseFiles($files ?: $_FILES));

        if (empty($server)) {
          $server = $_SERVER;
        }

        parent::__construct(
          $server['REQUEST_METHOD'] ?? 'GET',
          $server['REQUEST_URI'] ?? '/',
          iterator_to_array($this->parseServerHeaders($server))
        );
    }

    /**
     * Retrieve query string parameters.
     *
     * @return string[]
     */
    function getQuery() {
        return $this->query;
    }

    /**
     * Retrieve parameters provided in the request body.
     *
     * @return string[]
     */
    function getParsedBody() {
        return $this->xargs;
        // TODO check Content-Type is either application/x-www-form-urlencoded
        //      or multipart/form-data, and the request method is POST,
        //      otherwise content negotation
    }

    /**
     * Retrieve normalized file uploads.
     *
     * @return UploadedFile[]
     */
    function getFiles() {
        return $this->files;
    }

    private $specialHeaders = ['CONTENT_TYPE', 'CONTENT_LENGTH'];
    private $invalidHeaders = ['HTTP_PROXY'];

    private function parseServerHeaders(array $server) {
        foreach ($server as $key => $value) {
            if (strncmp($key, 'HTTP_', 5) === 0 &&
                !in_array($key, $this->invalidHeaders)
            ) {
                yield str_replace('_', '-', substr($key, 5)) => $value;
            } elseif (in_array($key, $this->specialHeaders)) {
                yield str_replace('_', '-', $key) => $value;
            }
        }
    }

    private function parseFiles(array $files) {
        foreach ($files as $key => $file) {
            if (!is_array($file)) {
                throw new \InvalidArgumentException('Invalid uploaded file');
            }

            if (!isset($file['error'])) {
                yield $key => iterator_to_array($this->parseFiles($file));
            } elseif (!is_array($file['error'])) {
                yield $key => new UploadedFile(
                    $file['tmp_name'],
                    $file['size'],
                    $file['error'],
                    $file['name'],
                    $file['type']
                );
            } else {
                $indexed = [];
                foreach ($file['error'] as $index => $_) {
                    $indexed[$index] = array(
                        'tmp_name' => $file['tmp_name'][$index],
                        'size'     => $file['size'][$index],
                        'error'    => $file['error'][$index],
                        'name'     => $file['name'][$index],
                        'type'     => $file['type'][$index],
                    );
                }

                yield $key => iterator_to_array($this->parseFiles($indexed));
            }
        }
    }
}