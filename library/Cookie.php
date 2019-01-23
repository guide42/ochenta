<?php declare(strict_types=1);

namespace ochenta;

/** Cookie representation. */
class Cookie {
    /** Namespace to be used to access `$_COOKIE[...]`. */
    protected/* string */ $name;

    /** Will be stored on the client's machine or deleted if empty string. */
    protected/* string */ $value;

    /** Unix timestamp indicating when will expire, usually `time() + $seconds`. */
    protected/* int */ $expires = 0;

    /** FQDN where is valid or `null` for the current hostname. */
    protected/* ?string */ $domain;

    /** Server's directory where is valid. */
    protected/* string */ $path = NULL;

    /** Send back to client over secure HTTPS connections only. */
    protected/* bool */ $secure = TRUE;

    /** Access through the HTTP protocol only. */
    protected/* bool */ $httpOnly = TRUE;

    /** Send back for cross-site requests. */
    protected/* ?string */ $sameSite = 'Strict';

    /** Cookie flags and modifiers. */
    private/* array */ $flags = [];

    /** Reference time for relative calculations. */
    private/* int */ $now = 0;

    function __construct(string $name, string $value, array $attributes=[], array $flags=[], ?int $now=NULL) {
        $this->now = $now ?: time();

        if (preg_match("/[=,; \t\r\n\013\014]/", $name) || empty($name)) {
            throw new \InvalidArgumentException('Invalid cookie name');
        }

        $this->name = $name;
        $this->value = $value;

        if (isset($attributes['Expires'])) {
            if ($attributes['Expires'] instanceof \DateTimeInterface) {
                $this->expires = $attributes['Expires']->getTimestamp();
            } elseif (is_string($attributes['Expires'])) {
                $this->expires = strtotime($attributes['Expires'], $this->now);
                if ($this->expires === FALSE) {
                    throw new \InvalidArgumentException('Invalid cookie expires attribute');
                }
            } else {
                $this->expires = intval($attributes['Expires']);
            }
        }

        if (isset($attributes['Domain'])) {
            $this->domain = strtolower(ltrim($attributes['Domain'], '.'));
        }

        if (isset($attributes['Path'])) {
            $this->path = $attributes['Path'];
        }

        if (isset($attributes['Secure'])) {
            $this->secure = boolval($attributes['Secure']);
        }

        if (isset($attributes['HttpOnly'])) {
            $this->httpOnly = boolval($attributes['HttpOnly']);
        }

        $this->flags = $flags + [
            'creation' => $this->now,
            'last-access' => $this->now,
            'persistent' => FALSE,
            'host-only' => FALSE,
        ];
    }

    function getPrefix(): string {
        if ($this->secure) {
            if ($this->path === '/' && $this->domain === NULL) {
                return '__Host-';
            }
            return '__Secure-';
        }
        return '';
    }

    function getName(): string {
        return $this->name;
    }

    function getValue(): string {
        return $this->value;
    }

    function getLifetime(): ?int {
        if ($this->expires) {
            return $this->expires - $this->now;
        }
        return NULL;
    }

    function isExpired(): bool {
        return $this->expires && $this->expires < $this->now;
    }

    function isSecure(): bool {
        return $this->secure;
    }

    function matches(Request $req): bool {
        if ($this->isExpired()) {
            return FALSE;
        }

        if ($this->domain) {
            // TODO domain canonicalization with punycode
            $domain = array_reverse(explode('.', $this->domain . '.'));
            $host = array_reverse(explode('.', $req->getHost() . '.'));

            if (count($domain) > count($host)) {
                return FALSE;
            }
            for ($i = 0; $i < count($domain); $i++) {
                if ($domain[$i] !== $host[$i]) {
                    return FALSE;
                }
            }

            // If the cookie is marked as host-only and we don't have an exact
            // match, reject the cookie
            if ($this->flags['host-only'] && count($domain) < count($host)) {
                return FALSE;
            }
        }

        if ($this->path && stripos($req->getTargetPath(), $this->path) !== 0) {
            return FALSE;
        }

        if ($this->isSecure() && !$req->isSecure()) {
            return FALSE;
        }

        return TRUE;
    }

    function __toString(): string {
        $ret = $this->getPrefix() . rawurlencode($this->name) . '=';

        if ($this->value === '') {
            $ret .= 'deleted; Expires=' . gmdate(\DateTime::COOKIE, $this->now - 31536042) . '; Max-Age=0';
        } else {
            $ret .= rawurlencode($this->value);

            if ($this->expires) {
                $ret .= '; Expires=' . gmdate(\DateTime::COOKIE, $this->expires);
            }
        }

        if ($this->path) {
            $ret .= '; Path=' . $this->path;
        }
        if ($this->domain) {
            $ret .= '; Domain=' . $this->domain;
        }
        if ($this->secure) {
            $ret .= '; Secure';
        }
        if ($this->httpOnly) {
            $ret .= '; HttpOnly';
        }

        return $ret;
    }
}