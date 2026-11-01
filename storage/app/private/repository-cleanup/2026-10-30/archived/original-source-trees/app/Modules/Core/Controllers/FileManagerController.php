<?php

namespace App\Modules\Core\Controllers;

use App\Models\SystemFile;
use App\Models\SystemSetting;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileManagerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:files-view', only: ['index']),
            new Middleware('permission:files-create', only: ['upload']),
            new Middleware('permission:files-edit', only: ['rename']),
            new Middleware('permission:files-delete', only: ['delete']),
            new Middleware('permission:files-download', only: ['download', 'downloadZip']),
            new Middleware('permission:settings-edit', only: ['setLoginBackground', 'setDefaultImage']),
        ];
    }

    private function getFileUrl($fileRecord)
    {
        $disk = Storage::disk('public');
        if (config('filesystems.disks.public.driver') === 'local') {
            return '/api/files/preview?f=' . rawurlencode($fileRecord->filename);
        }
        return $disk->url($fileRecord->path);
    }

    public function preview(Request $request)
    {
        $filename = $request->query('f');
        if (!is_string($filename) || !$filename || $filename === '.' || $filename === '..' || str_contains($filename, "\0") || str_contains($filename, '/') || str_contains($filename, '\\')) {
            abort(404);
        }

        abort_unless(config('filesystems.disks.public.driver') === 'local', 404);
        $absolutePath = Storage::disk('public')->path('uploads/' . $filename);
        if (!is_file($absolutePath)) {
            abort(404);
        }

        // Close the session early so streaming large files doesn't block the user's other requests
        if (session()->isStarted()) {
            session()->save();
        }

        $mime = (new \Symfony\Component\Mime\MimeTypes)->guessMimeType($absolutePath) ?: 'application/octet-stream';
        $inline = str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/') || $mime === 'application/pdf';
        $response = $inline ? response()->file($absolutePath) : response()->download($absolutePath, $filename);
        $response->headers->add([
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        if ($mime === 'image/svg+xml') $response->headers->set('Content-Security-Policy', 'sandbox');
        return $response;
    }

    public function index()
    {
        $files = SystemFile::latest()->get()->map(function ($fileRecord) {
            $url = $this->getFileUrl($fileRecord);

            $typeCategory = 'other';
            $icon = 'bi-file-earmark';
            $mime = (string) $fileRecord->mime_type;
            if (str_starts_with($mime, 'image/')) {
                $typeCategory = 'image';
                $icon = 'bi-file-earmark-image';
            } elseif (str_starts_with($mime, 'video/')) {
                $typeCategory = 'video';
                $icon = 'bi-file-earmark-play';
            } elseif ($mime === 'application/pdf' || str_starts_with($mime, 'text/') || preg_match('/(word|excel|powerpoint|officedocument|opendocument|rtf|json)/i', $mime)) {
                $typeCategory = 'document';
                $icon = $mime === 'application/pdf' ? 'bi-file-earmark-pdf' : 'bi-file-earmark-text';
            }

            return [
                'id' => (string) $fileRecord->id,
                'name' => $fileRecord->original_name, // Human readable name from DB
                'filename' => $fileRecord->filename, // Safe stored name
                'type' => $typeCategory,
                'icon' => $icon,
                'size' => $this->formatBytes($fileRecord->size),
                'modifiedDate' => ($fileRecord->updated_at ?? $fileRecord->created_at)?->format('M d, Y h:i A') ?? 'Unknown',
                'modifiedAt' => ($fileRecord->updated_at ?? $fileRecord->created_at)?->toIso8601String(),
                'sizeBytes' => (int) $fileRecord->size,
                'url' => $url,
                'typeLabel' => ucfirst($typeCategory),
            ];
        });



        // Add assets images
        $assetImagesPath = public_path('assets/images');
        $assetFiles = collect();
        if (is_dir($assetImagesPath)) {
            $iterator = new \DirectoryIterator($assetImagesPath);
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile() && in_array(strtolower($fileinfo->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) {
                    $url = asset('assets/images/' . $fileinfo->getFilename());
                    $assetFiles->push([
                        'id' => 'asset:' . $fileinfo->getFilename(),
                        'name' => $fileinfo->getFilename(),
                        'filename' => $fileinfo->getFilename(),
                        'type' => 'image',
                        'icon' => 'bi-file-earmark-image',
                        'size' => $this->formatBytes($fileinfo->getSize()),
                        'modifiedDate' => date('M d, Y h:i A', $fileinfo->getMTime()),
                        'modifiedAt' => date(DATE_ATOM, $fileinfo->getMTime()),
                        'sizeBytes' => $fileinfo->getSize(),
                        'url' => $url,
                        'typeLabel' => 'Image',
                        'isAsset' => true,
                    ]);
                }
            }
        }
        
        $allFiles = $files->concat($assetFiles);

        $configuredDefaultAvatar = SystemSetting::where('key', 'default_image_default_avatar_jpeg')->value('value') ?: '';
        $resolvedRoles = [
            'Login Background' => SystemSetting::where('key', 'login_background_image')->value('value') ?: asset('assets/images/background.png'),
            'App Logo (PNG)' => SystemSetting::where('key', 'default_image_logo_png')->value('value') ?: asset('assets/images/logo.png'),
            'Default Avatar' => $configuredDefaultAvatar ?: asset('assets/images/default_avatar.jpeg'),
            'Product Placeholder' => SystemSetting::where('key', 'default_image_product-placeholder_svg')->value('value') ?: asset('assets/images/product-placeholder.svg')
        ];

        $allFiles->transform(function ($item) use ($resolvedRoles) {
            $item['defaultRoles'] = [];
            $itemPath = $this->relativeFileUrl($item['url']);
            foreach ($resolvedRoles as $roleName => $resolvedUrl) {
                $rolePath = $this->relativeFileUrl($resolvedUrl);
                if ($item['url'] === $resolvedUrl || ($itemPath && $rolePath && $itemPath === $rolePath)) {
                    $item['defaultRoles'][] = $roleName;
                }
            }
            return $item;
        });

        return response()->json($allFiles->values())->header('X-Default-Avatar', rawurlencode($configuredDefaultAvatar));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');

        $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $originalName = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $originalName));
        abort_if(in_array($originalName, ['', '.', '..'], true) || mb_strlen($originalName) > 255, 422, 'Choose a file name between 1 and 255 characters');
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $extension = strtolower($file->guessExtension() ?: 'bin');
        if (in_array($extension, ['php', 'phtml', 'phar', 'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp'], true)) {
            $extension = 'bin';
        }
        $filename = time().'_'.Str::random(20).'.'.$extension;
        $path = 'uploads/'.$filename;
        $disk = null;
        $stream = null;
        try {
            $disk = Storage::disk('public');
            $stream = fopen($file->getRealPath(), 'rb');
            if (! is_resource($stream)) throw new \RuntimeException('The temporary uploaded file could not be opened.');
            // Use the configured disk's driver so write errors are not swallowed by throw=false.
            // The stream API preserves the disk's root, visibility and other storage settings.
            $disk->getDriver()->writeStream($path, $stream);
        } catch (\Throwable $error) {
            $reference = (string) Str::uuid();
            $diagnostics = $this->uploadStorageDiagnostics();
            $this->logUploadIssue('error', 'Files upload storage write failed', array_merge($diagnostics, [
                'reference' => $reference,
                'exception_class' => get_class($error),
                'reason' => $error->getMessage(),
            ]));
            if ($disk) $this->cleanupFailedUpload($disk, $path, $reference);

            $message = 'Upload storage is unavailable. Ask the administrator to check production storage.';
            if (isset($diagnostics['directory_writable']) && ! $diagnostics['directory_writable']) {
                $message = 'The upload folder is not writable by the server. Ask the administrator to correct its ownership or permissions.';
            } elseif (isset($diagnostics['free_space_bytes']) && $diagnostics['free_space_bytes'] < $file->getSize()) {
                $message = 'The server does not have enough free storage for this upload.';
            }
            return response()->json(['message' => $message.' Reference: '.$reference, 'reference' => $reference], 500);
        } finally {
            if (is_resource($stream)) fclose($stream);
        }

        try {
            $fileRecord = DB::transaction(fn () => SystemFile::create([
                'original_name' => $originalName,
                'filename' => $filename,
                'mime_type' => $mime,
                'size' => $file->getSize(),
                'path' => $path,
            ]));
        } catch (\Throwable $error) {
            $this->cleanupFailedUpload($disk, $path);
            throw $error;
        }

        return response()->json([
            'message' => 'File uploaded successfully',
            'path' => $this->getFileUrl($fileRecord),
        ]);
    }

    private function uploadStorageDiagnostics(): array
    {
        $driver = config('filesystems.disks.public.driver');
        $diagnostics = ['disk' => 'public', 'driver' => $driver];
        if ($driver !== 'local') return $diagnostics;

        $root = (string) config('filesystems.disks.public.root');
        $directory = rtrim($root, '/\\').DIRECTORY_SEPARATOR.'uploads';
        $ancestor = $directory;
        while (! is_dir($ancestor) && dirname($ancestor) !== $ancestor) $ancestor = dirname($ancestor);
        clearstatcache(true, $directory);
        $freeSpace = is_dir($ancestor) ? @disk_free_space($ancestor) : false;
        return array_merge($diagnostics, [
            'root' => $root,
            'upload_directory' => $directory,
            'directory_exists' => is_dir($directory),
            'directory_writable' => is_dir($directory) ? is_writable($directory) : is_writable($ancestor),
            'free_space_bytes' => $freeSpace === false ? null : $freeSpace,
            'process_uid' => function_exists('posix_geteuid') ? posix_geteuid() : null,
        ]);
    }

    private function logUploadIssue(string $level, string $message, array $context): void
    {
        try {
            Log::log($level, $message, $context);
        } catch (\Throwable $loggingError) {
            // Preserve diagnostics in the PHP server log if Laravel's log directory is also unwritable.
            error_log($message.' '.json_encode($context, JSON_UNESCAPED_SLASHES));
        }
    }

    private function cleanupFailedUpload($disk, string $path, ?string $reference = null): void
    {
        try {
            if ($disk->delete($path)) return;
        } catch (\Throwable $error) {
            $this->logUploadIssue('warning', 'Files failed-upload cleanup failed', ['reference' => $reference, 'reason' => $error->getMessage()]);
            return;
        }
        $this->logUploadIssue('warning', 'Files failed-upload cleanup failed', ['reference' => $reference]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|min:1',
        ]);

        $deleted = DB::transaction(function () use ($request) {
            $fileRecord = SystemFile::lockForUpdate()->find($request->input('id'));
            if (! $fileRecord) return false;
            $disk = Storage::disk('public');
            $references = array_unique([
                $this->relativeFileUrl($this->getFileUrl($fileRecord)),
                $this->relativeFileUrl($disk->url($fileRecord->path)),
            ]);
            $settings = SystemSetting::whereIn('key', [
                'login_background_image', 'default_image_logo_png', 'default_image_logo_svg',
                'default_image_default_avatar_jpeg', 'default_image_product-placeholder_svg',
            ])->get();
            foreach ($settings as $setting) {
                if (in_array($this->relativeFileUrl((string) $setting->value), $references, true)) $setting->delete();
            }
            $fileRecord->delete();
            if ($disk->exists($fileRecord->path)) {
                abort_unless($disk->delete($fileRecord->path), 500, 'Could not delete file');
            }
            return true;
        });
        return $deleted
            ? response()->json(['message' => 'File deleted successfully'])
            : response()->json(['error' => 'File not found'], 404);
    }

    public function rename(Request $request)
    {
        if (is_string($request->input('newName'))) $request->merge(['newName' => trim($request->input('newName'))]);
        $request->validate([
            'id' => 'required|integer|min:1',
            'newName' => ['required', 'string', 'max:255', 'not_in:.,..', function ($attribute, $value, $fail) {
                if (preg_match('/[\\\\\/\x00-\x1F\x7F]/', $value)) $fail('File names cannot contain path separators or control characters.');
            }],
        ]);

        $newName = $request->input('newName');
        $fileRecord = DB::transaction(function () use ($request, $newName) {
            $file = SystemFile::lockForUpdate()->find($request->input('id'));
            if (! $file) return null;
            // Logical rename preserves the physical filename and existing links.
            $file->original_name = $newName;
            $file->save();
            return $file;
        });
        if (! $fileRecord) return response()->json(['error' => 'File not found'], 404);

        $newUrl = $this->getFileUrl($fileRecord);

        return response()->json(['message' => 'File renamed successfully', 'url' => $newUrl, 'name' => $newName]);
    }

    public function download(Request $request)
    {
        $request->validate(['id' => ['required', 'integer', 'min:1']]);
        $file = SystemFile::findOrFail($request->input('id'));
        $disk = Storage::disk('public');
        abort_unless($disk->exists($file->path), 404);
        return $disk->download($file->path, $file->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function downloadZip(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => ['required', function ($attribute, $value, $fail) {
                if ((! is_string($value) && ! is_int($value)) || (! ctype_digit((string) $value) && ! str_starts_with((string) $value, 'asset:'))) $fail('Invalid file selection.');
            }],
        ]);

        $ids = collect($request->input('ids'))->unique();
        $fileRecords = SystemFile::whereIn('id', $ids->filter(fn ($id) => ctype_digit((string) $id)))->get();
        $disk = Storage::disk('public');
        $zipPath = tempnam(sys_get_temp_dir(), 'files_zip_');
        abort_if($zipPath === false, 500, 'Could not create zip file');
        $zip = new \ZipArchive;
        $opened = false;
        $entries = 0;
        $names = [];

        try {
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                abort(500, 'Could not create zip file');
            }
            $opened = true;
            $addEntry = function (string $name, ?string $contents = null, ?string $sourcePath = null) use ($zip, &$entries, &$names) {
                $name = basename(str_replace('\\', '/', $name));
                if ($name === '' || $name === '.' || $name === '..') $name = 'file';
                $candidate = $name;
                $suffix = 1;
                while (isset($names[$candidate])) $candidate = ($suffix++).'_'.$name;
                if (!($sourcePath !== null ? $zip->addFile($sourcePath, $candidate) : $zip->addFromString($candidate, $contents))) abort(500, 'Could not add file to zip');
                $names[$candidate] = true;
                $entries++;
            };
            foreach ($fileRecords as $record) {
                if ($disk->exists($record->path)) {
                    if (config('filesystems.disks.public.driver') === 'local') $addEntry($record->original_name, null, $disk->path($record->path));
                    else $addEntry($record->original_name, $disk->get($record->path));
                }
            }
            foreach ($ids as $id) {
                if (!str_starts_with((string) $id, 'asset:')) continue;
                $name = substr($id, 6);
                if (basename($name) !== $name || str_contains($name, '\\')) continue;
                $path = public_path('assets/images/'.$name);
                if (is_file($path)) $addEntry($name, null, $path);
            }
            abort_if($entries === 0, 404, 'No downloadable files found');
            if (!$zip->close()) abort(500, 'Could not finish zip file');
            $opened = false;
        } catch (\Throwable $error) {
            if ($opened) $zip->close();
            @unlink($zipPath);
            throw $error;
        }

        return response()->download($zipPath, 'download_'.time().'.zip')->deleteFileAfterSend(true);
    }

    public function setLoginBackground(Request $request)
    {
        $request->validate([
            'file_id' => ['required_without:url', 'nullable', 'string'],
            'url' => ['required_without:file_id', 'nullable', 'string'],
        ]);
        $fileId = $request->input('file_id');
        if (! $fileId) {
            // Retain compatibility with callers that send a listed file URL.
            $relative = $this->relativeFileUrl($request->input('url'));
            if (str_starts_with($relative, '/assets/images/')) {
                $fileId = 'asset:'.substr($relative, strlen('/assets/images/'));
            } else {
                $record = SystemFile::all()->first(function ($file) use ($relative) {
                    return in_array($relative, [
                        $this->relativeFileUrl($this->getFileUrl($file)),
                        $this->relativeFileUrl(Storage::disk('public')->url($file->path)),
                    ], true);
                });
                abort_unless($record, 422, 'Choose an existing image file');
                $fileId = (string) $record->id;
            }
        }
        DB::transaction(function () use ($fileId) {
            $path = $this->imageUrlForId($fileId);
            SystemSetting::updateOrCreate(['key' => 'login_background_image'], ['value' => $path]);
        });

        return response()->json(['message' => 'Login background updated successfully']);
    }

    public function setDefaultImage(Request $request)
    {
        $request->validate([
            'file_id' => 'required|string',
            'target_name' => 'required|string|in:logo.png,logo.svg,default_avatar.jpeg,product-placeholder.svg',
        ]);

        $targetName = $request->input('target_name');
        $url = DB::transaction(function () use ($request, $targetName) {
            $url = $this->imageUrlForId($request->input('file_id'));
            $settingKey = 'default_image_' . str_replace('.', '_', $targetName);
            SystemSetting::updateOrCreate(['key' => $settingKey], ['value' => $url]);
            return $url;
        });

        $response = ['message' => 'Default image updated successfully', 'url' => $url];
        if ($targetName === 'logo.png') {
            $response['brand_logo'] = \App\Services\BrandLogo::resolve($url);
        }

        return response()->json($response);
    }

    private function imageUrlForId(string $fileId): string
    {
        $url = '';
        if (str_starts_with($fileId, 'asset:')) {
            $filename = substr($fileId, 6);
            abort_unless(basename($filename) === $filename && !str_contains($filename, '\\') && is_file(public_path('assets/images/'.$filename)), 404);
            abort_unless(in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']), 422, 'Choose an image file');
            $url = '/assets/images/' . $filename;
        } else {
            $fileRecord = SystemFile::lockForUpdate()->findOrFail($fileId);
            abort_unless(str_starts_with((string) $fileRecord->mime_type, 'image/'), 422, 'Choose an image file');
            abort_unless(Storage::disk('public')->exists($fileRecord->path), 404);
            $url = $this->getFileUrl($fileRecord);
            if (config('filesystems.disks.public.driver') === 'local') $url = $this->relativeFileUrl($url);
        }

        return $url;
    }

    private function relativeFileUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $query = parse_url($url, PHP_URL_QUERY);

        return $path . ($query ? '?' . $query : '');
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
