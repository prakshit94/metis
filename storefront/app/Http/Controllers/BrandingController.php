<?php

namespace Storefront\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Routing\Controller;

class BrandingController extends Controller
{
    public function __invoke()
    {
        $configured = trim((string) SystemSetting::where('key', 'default_image_logo_png')->value('value'));
        $parts = parse_url($configured);
        $host = strtolower($parts['host'] ?? '');
        $localHosts = ['localhost', '127.0.0.1', '::1', strtolower((string) parse_url(config('storefront.staff_url'), PHP_URL_HOST)), strtolower((string) parse_url(config('app.url'), PHP_URL_HOST))];
        if ($parts !== false && ! isset($parts['user']) && ! isset($parts['pass']) && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) && $host !== '' && ! in_array($host, $localHosts, true)) {
            return redirect()->away($configured)->header('Cache-Control', 'no-cache');
        }
        $public = realpath(dirname(base_path()).'/public');
        $path = $parts !== false ? rawurldecode($parts['path'] ?? '') : '';
        $file = $public && ! str_contains($path, "\0") ? realpath($public.'/'.ltrim($path, '/')) : false;
        if (! $file || ! str_starts_with($file, $public.DIRECTORY_SEPARATOR) || ! is_file($file) || ! in_array(mime_content_type($file), ['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/avif'], true)) {
            $file = public_path('brand-logo.png');
        }

        return response()->file($file, ['Cache-Control' => 'no-cache', 'X-Content-Type-Options' => 'nosniff']);
    }
}
