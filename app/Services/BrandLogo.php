<?php

declare(strict_types=1);

namespace App\Services;

/** Resolve public branding assets without depending on APP_URL's host/port. */
final class BrandLogo
{
    public static function resolve(?string $configured): array
    {
        $fallback = self::local('/assets/images/logo.png');
        $value = trim((string)$configured);
        if ($value === '') return ['src' => $fallback, 'fallback' => $fallback];
        $parts = parse_url($value);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) return ['src' => $fallback, 'fallback' => $fallback];
        $scheme = strtolower($parts['scheme'] ?? '');
        if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) return ['src' => $fallback, 'fallback' => $fallback];
        $host = strtolower($parts['host'] ?? '');
        $localHosts = ['localhost', '127.0.0.1', '::1', strtolower((string)parse_url((string)config('app.url'), PHP_URL_HOST))];
        if ($host !== '' && !in_array($host, $localHosts, true)) {
            return ['src' => $value, 'fallback' => $fallback];
        }
        $path = '/'.ltrim($parts['path'] ?? '', '/');
        $decoded = rawurldecode($path);
        if (str_contains($decoded, "\0") || str_contains($decoded, '\\') || preg_match('~(?:^|/)\.\.?(?:/|$)~', $decoded)
            || !is_file(public_path(ltrim($decoded, '/')))) {
            return ['src' => $fallback, 'fallback' => $fallback];
        }
        $src = self::local($path, $parts['query'] ?? '', $parts['fragment'] ?? '');
        return ['src' => $src, 'fallback' => $fallback];
    }

    private static function local(string $path, string $query = '', string $fragment = ''): string
    {
        $file = public_path(ltrim(rawurldecode($path), '/'));
        if (is_file($file)) {
            $version = 'v='.filemtime($file);
            $query = preg_match('/(?:^|&)v=[^&]*/', $query)
                ? preg_replace('/(^|&)v=[^&]*/', '$1'.$version, $query)
                : ($query !== '' ? $query.'&' : '').$version;
        }
        return $path.($query !== '' ? '?'.$query : '').($fragment !== '' ? '#'.$fragment : '');
    }
}
