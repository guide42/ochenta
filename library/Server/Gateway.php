<?php

namespace Ochenta\Server;

use Ochenta\ServerRequest;

class Gateway
{
    protected $responder;

    function __construct(callable $responder) {
        $this->responder = $responder;
    }

    function __invoke(ServerRequest $req) {
        $responder = $this->responder;
        $generator = $responder($req, function(int $status, array $headers) {
            if (headers_sent()) {
                throw new \RuntimeException('Headers already sent');
            }

            header(sprintf('HTTP/1.1 %d', $status));

            foreach ($headers as $name => $values) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                $first = TRUE;
                foreach ($values as $value) {
                  header("$name: $value", $first);
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
                call_user_func($close);
            }
        }
    }
}