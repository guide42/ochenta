<?php declare(strict_types=1);

namespace ochenta\accept;

use ochenta\Request;

/** Negotitate from a list of content types and returns all accepted. */
function mediatypes(Request $req, array $available): array {
    return negotiate($available, $req->getAcceptMediaType() ?: [],
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

/** Negotitate from a list of charsets and returns all accepted. */
function charsets(Request $req, array $available): array {
    return negotiate($available, $req->getAcceptCharset() ?: [],
        function(string $available, string $acceptable): bool {
            return $acceptable == '*' || !strcasecmp($acceptable, $available);
        });
}

/** Negotitate from a list of encodings and returns all accepted. */
function encodings(Request $req, array $available): array {
    return negotiate($available, $req->getAcceptEncoding() ?: [],
        function(string $available, string $acceptable): bool {
            return $acceptable == '*' || !strcasecmp($acceptable, $available);
        });
}

/** Negotitate from a list of languages and returns all accepted. */
function languages(Request $req, array $available): array {
    return negotiate($available, $req->getAcceptLanguage() ?: [],
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
 *  all that call the match function with one available string and one
 *  acceptable string return true, will return empty list otherwise.
 */
function negotiate(array $available, array $acceptable, callable $match): array {
    if (!$available || !$acceptable) {
        return $available;
    }
    $accepted = [];
    foreach ($acceptable as $accept => $attrs) {
        foreach ($available as $avail) {
            if ($match($avail, $accept)) {
                $accepted[] = $avail;
            }
        }
    }
    return $accepted;
}
