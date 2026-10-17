<?php

namespace App\Modules\Core\Controllers;

use App\Models\SystemFile;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileManagerController extends Controller
{
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
        if (!is_string($filename) || !$filename || $filename === '.' || $filename === '..' || str_contains($filename, '/') || str_contains($filename, '\\')) {
            abort(404);
        }

        $absolutePath = storage_path('app/public/uploads/' . $filename);
        if (!is_file($absolutePath)) {
            abort(404);
        }

        // Close the session early so streaming large files doesn't block the user's other requests
        if (session()->isStarted()) {
            session()->save();
        }

        return response()->file($absolutePath, [
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=31536000'
        ]);
    }

    public function index()
    {
        $files = SystemFile::latest()->get()->map(function ($fileRecord) {
            $disk = Storage::disk('public');
            $url = $this->getFileUrl($fileRecord);

            $typeCategory = 'other';
            $icon = 'bi-file-earmark';
            if (str_starts_with($fileRecord->mime_type, 'image/')) {
                $typeCategory = 'image';
                $icon = 'bi-file-earmark-image';
            } elseif (str_starts_with($fileRecord->mime_type, 'video/')) {
                $typeCategory = 'video';
                $icon = 'bi-file-earmark-play';
            } elseif (str_starts_with($fileRecord->mime_type, 'application/pdf')) {
                $typeCategory = 'document';
                $icon = 'bi-file-earmark-pdf';
            }

            return [
                'id' => (string) $fileRecord->id,
                'name' => $fileRecord->original_name, // Human readable name from DB
                'filename' => $fileRecord->filename, // Safe stored name
                'type' => $typeCategory,
                'icon' => $icon,
                'size' => $this->formatBytes($fileRecord->size),
                'modifiedDate' => $fileRecord->created_at?->format('M d, Y h:i A') ?? 'Unknown',
                'modifiedAt' => $fileRecord->created_at?->toIso8601String(),
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
            'App Logo (SVG)' => SystemSetting::where('key', 'default_image_logo_svg')->value('value') ?: asset('assets/images/logo.svg'),
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

        // Ensure secure, unique physical filename on disk
        $originalName = $file->getClientOriginalName();
        $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');

        $fileRecord = SystemFile::create([
            'original_name' => $originalName,
            'filename' => $filename,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'path' => $this->getFileUrl($fileRecord),
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $id = $request->input('id');
        $fileRecord = SystemFile::find($id);

        if ($fileRecord) {
            if (Storage::disk('public')->exists($fileRecord->path)) {
                abort_unless(Storage::disk('public')->delete($fileRecord->path), 500, 'Could not delete file');
            }

            // Remove only settings that reference this exact file, including its query string.
            $url = $this->getFileUrl($fileRecord);
            $storageUrl = Storage::disk('public')->url($fileRecord->path);
            $references = array_unique([$url, $this->relativeFileUrl($url), $storageUrl, $this->relativeFileUrl($storageUrl)]);
            SystemSetting::whereIn('key', [
                'login_background_image', 'default_image_logo_png', 'default_image_logo_svg',
                'default_image_default_avatar_jpeg', 'default_image_product-placeholder_svg',
            ])->whereIn('value', $references)->delete();
            $fileRecord->delete();

            return response()->json(['message' => 'File deleted successfully']);
        }

        return response()->json(['error' => 'File not found'], 404);
    }

    public function rename(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'newName' => 'required|string|max:255|not_in:.,..',
        ]);

        $id = $request->input('id');
        $newName = basename($request->input('newName'));

        $fileRecord = SystemFile::find($id);

        if (! $fileRecord) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // We only rename the logical presentation name in the DB.
        // We do not change the actual file on disk to preserve caching/links.
        $fileRecord->original_name = $newName;
        $fileRecord->save();

        $disk = Storage::disk('public');
        $newUrl = $this->getFileUrl($fileRecord);

        return response()->json(['message' => 'File renamed successfully', 'url' => $newUrl]);
    }

    public function downloadZip(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required',
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
            $addEntry = function (string $name, string $contents) use ($zip, &$entries, &$names) {
                $name = basename(str_replace('\\', '/', $name));
                if ($name === '' || $name === '.' || $name === '..') $name = 'file';
                $candidate = $name;
                $suffix = 1;
                while (isset($names[$candidate])) $candidate = ($suffix++).'_'.$name;
                if (!$zip->addFromString($candidate, $contents)) abort(500, 'Could not add file to zip');
                $names[$candidate] = true;
                $entries++;
            };
            foreach ($fileRecords as $record) {
                if ($disk->exists($record->path)) $addEntry($record->original_name, $disk->get($record->path));
            }
            foreach ($ids as $id) {
                if (!str_starts_with((string) $id, 'asset:')) continue;
                $name = substr($id, 6);
                if (basename($name) !== $name || str_contains($name, '\\')) continue;
                $path = public_path('assets/images/'.$name);
                if (is_file($path)) $addEntry($name, file_get_contents($path));
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
            'url' => 'required|string',
        ]);

        $url = $request->input('url');
        $path = $this->relativeFileUrl($url);

        SystemSetting::updateOrCreate(
            ['key' => 'login_background_image'],
            ['value' => $path]
        );

        return response()->json(['message' => 'Login background updated successfully']);
    }

    public function setDefaultImage(Request $request)
    {
        $request->validate([
            'file_id' => 'required|string',
            'target_name' => 'required|string|in:logo.png,logo.svg,default_avatar.jpeg,product-placeholder.svg',
        ]);

        $fileId = $request->input('file_id');
        $targetName = $request->input('target_name');

        $url = '';
        if (str_starts_with($fileId, 'asset:')) {
            $filename = substr($fileId, 6);
            abort_unless(basename($filename) === $filename && !str_contains($filename, '\\') && is_file(public_path('assets/images/'.$filename)), 404);
            abort_unless(in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']), 422, 'Choose an image file');
            $url = '/assets/images/' . $filename;
        } else {
            $fileRecord = SystemFile::findOrFail($fileId);
            abort_unless(str_starts_with($fileRecord->mime_type, 'image/'), 422, 'Choose an image file');
            abort_unless(Storage::disk('public')->exists($fileRecord->path), 404);
            $url = $this->getFileUrl($fileRecord);
            $url = $this->relativeFileUrl($url);
        }

        $settingKey = 'default_image_' . str_replace('.', '_', $targetName);

        SystemSetting::updateOrCreate(
            ['key' => $settingKey],
            ['value' => $url]
        );

        return response()->json(['message' => 'Default image updated successfully', 'url' => $url]);
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
