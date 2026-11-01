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
        if (($parts['path'] ?? '') === '/api/files/preview') {
            $file = self::previewFile($parts);
            return ['src' => $file ? '/brand-logo?v='.substr(sha1($file.'|'.filemtime($file)), 0, 12) : $fallback, 'fallback' => $fallback];
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

    /** Resolve only a File Manager upload, never an arbitrary URL or filesystem path. */
    public static function previewFile(array $parts): ?string
    {
        if (($parts['path'] ?? '') !== '/api/files/preview' || config('filesystems.disks.public.driver') !== 'local') return null;
        parse_str($parts['query'] ?? '', $query);
        $name = $query['f'] ?? null;
        if (!is_string($name) || $name === '' || $name === '.' || $name === '..'
            || str_contains($name, "\0") || str_contains($name, '/') || str_contains($name, '\\')) return null;
        $base = realpath(\Illuminate\Support\Facades\Storage::disk('public')->path('uploads'));
        $file = $base ? realpath($base.'/'.$name) : false;
        return $file && str_starts_with($file, $base.DIRECTORY_SEPARATOR) && is_file($file) ? $file : null;
    }

    private static function local(string $path, string $query = '', string $fragment = ''): string
    {
        $file = public_path(ltrim(rawurldecode($path), '/'));
        // Use the small bundled copy only while it is newer than the source logo.
        $small = public_path('assets/images/logo-small.webp');
        if (in_array($path, ['/assets/images/logo.png', '/storefront/brand-logo.png'], true)
            && is_file($file) && is_file($small) && filemtime($small) >= filemtime($file)) {
            $path = '/assets/images/logo-small.webp';
            $file = $small;
        }
        if (is_file($file)) {
            $version = 'v='.filemtime($file);
            $query = preg_match('/(?:^|&)v=[^&]*/', $query)
                ? preg_replace('/(^|&)v=[^&]*/', '$1'.$version, $query)
                : ($query !== '' ? $query.'&' : '').$version;
        }
        return $path.($query !== '' ? '?'.$query : '').($fragment !== '' ? '#'.$fragment : '');
    }
}
