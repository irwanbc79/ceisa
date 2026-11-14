<?php

namespace App\Support;

class SafeUrl
{
    /**
     * Validate that a user-supplied base URL is safe for outbound HTTP requests (SSRF guard).
     */
    public static function isSafeExternalBaseUrl(string $url): bool
    {
        $parsed = parse_url(trim($url));
        if (! is_array($parsed)) {
            return false;
        }

        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
        $allowedSchemes = app()->environment('local', 'testing') ? ['https', 'http'] : ['https'];
        if (! in_array($scheme, $allowedSchemes, true)) {
            return false;
        }

        if (! empty($parsed['user']) || ! empty($parsed['pass'])) {
            return false;
        }

        $host = strtolower((string) ($parsed['host'] ?? ''));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        // Direct IP literal check
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host);
        }

        // Resolve hostname when possible; reject if any resolved IP is private/reserved.
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (is_array($records) && $records !== []) {
            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;
                if (is_string($ip) && ! self::isPublicIp($ip)) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * Validate query-supplied path or billing code so it cannot inject traversal or URLs.
     */
    public static function isSafeCeisaParam(?string $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || strlen($trimmed) > 255) {
            return false;
        }

        if (str_contains($trimmed, '..') || str_contains($trimmed, '\\') || preg_match('/[\r\n\0]/', $trimmed)) {
            return false;
        }

        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $trimmed)) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z0-9._\/-]+$/', $trimmed);
    }

    public static function sanitizeFilename(string $filename): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?? 'download';

        return ltrim($sanitized, '.') ?: 'download';
    }

    /**
     * Neutralize spreadsheet formula prefixes (=, +, -, @, tab, CR) before CSV export.
     */
    public static function csvCell(mixed $value): string
    {
        $str = (string) ($value ?? '');
        if ($str !== '' && preg_match('/^[=+\-@\t\r]/', $str)) {
            return "'".$str;
        }

        return $str;
    }
}
