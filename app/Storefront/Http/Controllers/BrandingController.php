<?php

namespace App\Storefront\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Routing\Controller;

class BrandingController extends Controller
{
    public function __invoke(\Illuminate\Http\Request $request)
    {
        $configured = trim((string) SystemSetting::where('key', 'default_image_logo_png')->value('value'));
        $parts = parse_url($configured);
        $host = strtolower($parts['host'] ?? '');
        $localHosts = ['localhost', '127.0.0.1', '::1', strtolower((string) parse_url(config('app.url'), PHP_URL_HOST))];
        if ($parts !== false && ! isset($parts['user']) && ! isset($parts['pass']) && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) && $host !== '' && ! in_array($host, $localHosts, true)) {
            return redirect()->away($configured)->header('Cache-Control', 'no-cache');
        }
        $path = $parts !== false ? '/'.ltrim(rawurldecode($parts['path'] ?? ''), '/') : '';
        // Uploaded logos live behind public/storage's symlink. Validate against
        // the upload root instead of rejecting that legitimate symlink target.
        $uploaded = str_starts_with($path, '/storage/');
        $base = realpath($uploaded ? storage_path('app/public') : public_path());
        $relative = $uploaded ? substr($path, strlen('/storage/')) : ltrim($path, '/');
        $file = $base && ! str_contains($path, "\0") ? realpath($base.'/'.$relative) : false;
        if (! $file || ! str_starts_with($file, $base.DIRECTORY_SEPARATOR) || ! is_file($file) || ! in_array(mime_content_type($file), ['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/avif'], true)) {
            $file = public_path('storefront/brand-logo.png');
        }

        $small = public_path('assets/images/logo-small.webp');
        if (in_array($file, [public_path('storefront/brand-logo.png'), public_path('assets/images/logo.png')], true)
            && is_file($small) && filemtime($small) >= filemtime($file)) $file = $small;

        $response = response()->file($file, ['Cache-Control' => 'public, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
        $response->setAutoEtag();
        $response->setAutoLastModified();
        $response->isNotModified($request);
        return $response;
    }
}
