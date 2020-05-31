<?php declare(strict_types=1);

namespace ochenta;

/** Content negotiation from Request object. */
class Accept {
    /** Any Request object from where retrieve Accept* headers. */
    protected /* Request */ $req;

    /** Service that accepts a Request object to negotitate the response
     *  content type, charset, encoding and language. All four functions
     *
     *      mediatype()
     *      charset()
     *      encoding()
     *      language()
     *
     *  accept a list of available options and will return the best match or
     *  null if none is accepted.
     */
    function __construct(Request $req) {
        $this->req = $req;
    }

    /** Retrieve the Request to retrieve the Accept* headers. */
    function getRequest(): Request {
        return $this->req;
    }

    /** Negotitate from a list of content types and return the best match. */
    function mediatype(array $available): ?string {
        return $this->negotiate($available, $this->req->getAcceptMediaType() ?: [],
            function(string $available, string $acceptable): bool {
                if (strpos($available, '/') === FALSE ||
                    strpos($acceptable, '/') === FALSE) {
                        throw new \UnexpectedValueException('Invalid media type: missing separator');
                }
                list($availType, $availSub) = explode('/', $available, 2);
                list($acceptType, $acceptSub) = explode('/', $acceptable, 2);

                if (($acceptType === '*' ||
                    !strcasecmp($acceptType, $availType)) &&
                    ($acceptSub === '*' ||
                    !strcasecmp($acceptSub, $availSub))) {
                        return TRUE;
                }

                if (strpos($availSub, '+') === FALSE ||
                    strpos($acceptSub, '+') === FALSE) {
                        return FALSE;
                }

                list($availSub, $availPlus) = explode('+', $availSub, 2);
                list($acceptSub, $acceptPlus) = explode('+', $acceptSub, 2);

                if (($acceptSub === '*' ||
                    !strcasecmp($acceptSub, $availSub)) &&
                    ($acceptPlus === '*' ||
                    !strcasecmp($acceptPlus, $availPlus))) {
                        return TRUE;
                }

                return FALSE;
            });
    }

    /** Negotitate from a list of charsets and return the best match. */
    function charset(array $available): ?string {
        return $this->negotiate($available, $this->req->getAcceptCharset() ?: [],
            function(string $available, string $acceptable): bool {
                return $acceptable == '*' || !strcasecmp($acceptable, $available);
            });
    }

    /** Negotitate from a list of encodings and return the best match. */
    function encoding(array $available): ?string {
        return $this->negotiate($available, $this->req->getAcceptEncoding() ?: [],
            function(string $available, string $acceptable): bool {
                return $acceptable == '*' || !strcasecmp($acceptable, $available);
            });
    }

    /** Negotitate from a list of languages and return the best match. */
    function language(array $available): ?string {
        return $this->negotiate($available, $this->req->getAcceptLanguage() ?: [],
            function(string $available, string $acceptable): bool {
                $availParts = explode('-', $available);
                $acceptParts = explode('-', $acceptable);

                $langEquals = $acceptParts[0] === '*'
                    || !strcasecmp($acceptParts[0], $availParts[0]);
                $subEquals = !isset($acceptParts[1])
                    || $acceptParts[1] === '*'
                    || !isset($availParts[1])
                    || !strcasecmp($acceptParts[1], $availParts[1]);

                return $langEquals && $subEquals;
            });
    }

    /** Loop over acceptable header values and over available values and return
     *  the first one that call the match function with one available string and
     *  one acceptable string return true, will return null otherwise.
     */
    private function negotiate(array $available, array $acceptable, callable $match): ?string {
        if (!$available) {
            return NULL;
        }
        if (!$acceptable) {
            return array_shift($available);
        }
        foreach ($acceptable as $accept => $attrs) {
            foreach ($available as $avail) {
                if ($match($avail, $accept)) {
                    return $avail;
                }
            }
        }
        return NULL;
    }
}