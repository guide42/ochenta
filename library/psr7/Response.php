<?php declare(strict_types=1);

namespace ochenta\psr7;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use ochenta\Response as OchentaResponse;

/** HTTP response implementation. */
class Response extends OchentaResponse implements ResponseInterface
{
    use MessageTrait;

    /** @var array<int, string> */
    protected $phrases = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /** @var string|null */
    protected $reasonPhrase;

    function __construct(int $statusCode=200, array $headers=[], $body=NULL) {
        foreach ($headers as $name => $header) {
            $this->headerNames[strtoupper($name)] = $name;
        }

        parent::__construct($statusCode, $headers, $body);

        $this->headerNames += [ // default headers
            'CACHE-CONTROL' => 'Cache-Control',
            'CONTENT-TYPE' => 'Content-Type',
        ];
    }

    function withStatus(/*int */$code, /*string */$reasonPhrase=''): self {
        $new = new self($code, $this->headers, $this->body);
        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }

    function getReasonPhrase(): string {
        if (!empty($this->reasonPhrase)) {
            return $this->reasonPhrase;
        }
        return $this->phrases[$this->statusCode] ?? '';
    }
}