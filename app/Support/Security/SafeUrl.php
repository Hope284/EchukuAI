<?php

declare(strict_types=1);

namespace App\Support\Security;

final class SafeUrl
{
    public static function normalize(?string $value): ?string
    {
        $url = trim((string) $value);

        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_contains($url, '\\')) {
            return $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true) && is_string($host) && $host !== ''
            ? $url
            : null;
    }

    public static function isExternal(string $url, ?string $currentHost = null): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        return ! hash_equals(strtolower((string) $currentHost), strtolower($host));
    }
}
