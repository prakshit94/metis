<?php

namespace App\Modules\Core\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\SystemSetting;
use App\Models\SystemFile;

class FileManagerController extends Controller
{
    public function index()
    {
        $files = SystemFile::latest()->get()->map(function ($fileRecord) {
            $disk = Storage::disk('public');
            $url = $disk->url($fileRecord->path);

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
                'modifiedDate' => $fileRecord->created_at->format('M d, Y h:i A'),
                'url' => $url,
                'typeLabel' => ucfirst($typeCategory)
            ];
        });

        // Add login background indicator if exists
        $loginBg = SystemSetting::where('key', 'login_background_image')->first();
        if ($loginBg && $loginBg->value) {
            $files->transform(function ($item) use ($loginBg) {
                $itemPath = parse_url($item['url'], PHP_URL_PATH);
                $bgPath = parse_url($loginBg->value, PHP_URL_PATH);
                if ($item['url'] === $loginBg->value || ($itemPath && $bgPath && $itemPath === $bgPath)) {
                    $item['isLoginBackground'] = true;
                }
                return $item;
            });
        }

        return response()->json($files->values());
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240' // 10MB max
        ]);

        $file = $request->file('file');
        
        // Ensure secure, unique physical filename on disk
        $originalName = $file->getClientOriginalName();
        $filename = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');

        SystemFile::create([
            'original_name' => $originalName,
            'filename' => $filename,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'path' => $path
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'path' => Storage::disk('public')->url($path)
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $id = $request->input('id');
        $fileRecord = SystemFile::find($id);

        if ($fileRecord) {
            if (Storage::disk('public')->exists($fileRecord->path)) {
                Storage::disk('public')->delete($fileRecord->path);
                
                // If it was the login background, clear it
                $url = Storage::disk('public')->url($fileRecord->path);
                $pathOnly = parse_url($url, PHP_URL_PATH) ?: $url;
                SystemSetting::where('key', 'login_background_image')
                    ->where(function($query) use ($url, $pathOnly) {
                        $query->where('value', $url)
                              ->orWhere('value', $pathOnly);
                    })->delete();
            }
            $fileRecord->delete();

            return response()->json(['message' => 'File deleted successfully']);
        }

        return response()->json(['error' => 'File not found'], 404);
    }

    public function rename(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'newName' => 'required|string',
        ]);

        $id = $request->input('id');
        $newName = basename($request->input('newName'));

        $fileRecord = SystemFile::find($id);

        if (!$fileRecord) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // We only rename the logical presentation name in the DB.
        // We do not change the actual file on disk to preserve caching/links.
        $fileRecord->original_name = $newName;
        $fileRecord->save();

        $disk = Storage::disk('public');
        $newUrl = $disk->url($fileRecord->path);

        return response()->json(['message' => 'File renamed successfully', 'url' => $newUrl]);
    }

    public function downloadZip(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required'
        ]);

        $ids = $request->input('ids');
        $fileRecords = SystemFile::whereIn('id', $ids)->get();
        $disk = Storage::disk('public');

        $zipName = 'download_' . time() . '.zip';
        // Make sure uploads directory exists in case it doesn't
        if (!$disk->exists('uploads')) {
            $disk->makeDirectory('uploads');
        }
        $zipPath = storage_path('app/public/uploads/' . $zipName);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($fileRecords as $fileRecord) {
                if ($disk->exists($fileRecord->path)) {
                    // Use original_name for the file inside the zip
                    $zip->addFile(storage_path('app/public/' . $fileRecord->path), $fileRecord->original_name);
                }
            }
            $zip->close();
        } else {
            return response()->json(['error' => 'Could not create zip file'], 500);
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function setLoginBackground(Request $request)
    {
        $request->validate([
            'url' => 'required|string'
        ]);

        $url = $request->input('url');
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        SystemSetting::updateOrCreate(
            ['key' => 'login_background_image'],
            ['value' => $path]
        );

        return response()->json(['message' => 'Login background updated successfully']);
    }

    private function formatBytes($bytes, $precision = 2) { 
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        $bytes /= (1 << (10 * $pow)); 

        return round($bytes, $precision) . ' ' . $units[$pow]; 
    } 
}
