<?php

declare(strict_types=1);

namespace App\Support\Security;

use Illuminate\Support\Facades\Http;

final class SafeRemoteUrl
{
    public static function isAllowed(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');
        $port = isset($parts['port']) ? (int) $parts['port'] : null;

        if (! in_array($scheme, ['http', 'https'], true)
            || $host === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || ($port !== null && ! in_array($port, [80, 443], true))) {
            return false;
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : (gethostbynamel($host) ?: []);

        if ($addresses === []) {
            return false;
        }

        foreach ($addresses as $address) {
            if (filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) === false) {
                return false;
            }
        }

        return true;
    }

    public static function fetchText(string $url, int $maxBytes = 2_000_000): ?string
    {
        if (! self::isAllowed($url)) {
            return null;
        }

        $response = Http::timeout(12)
            ->withOptions(['allow_redirects' => false])
            ->withHeaders(['User-Agent' => 'DZEVA/1.2 content fetcher'])
            ->get($url);

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();

        return strlen($body) <= $maxBytes ? $body : null;
    }
}
