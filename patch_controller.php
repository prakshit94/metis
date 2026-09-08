<?php
$file = 'app/Modules/Core/Controllers/VillageController.php';
$content = file_get_contents($file);

$search1 = <<<TXT
    public function syncIndiaPostPincodes(Request \$request, IndiaPostProvider \$indiaPostProvider): JsonResponse
    {
        \$pincode = \$request->input('pincode');
TXT;

$replace1 = <<<TXT
    public function syncIndiaPostPincodes(Request \$request, IndiaPostProvider \$indiaPostProvider): JsonResponse
    {
        if (\$request->input('action') === 'stop') {
            \Illuminate\Support\Facades\Cache::forget('syncing_indiapost_pincodes');
            \Illuminate\Support\Facades\Cache::forget('syncing_indiapost_pincodes_query');
            return response()->json(['success' => true, 'message' => 'Sync stopped successfully.']);
        }

        \$pincode = \$request->input('pincode');
        \Illuminate\Support\Facades\Cache::put('syncing_indiapost_pincodes', true, now()->addMinutes(15));
        \Illuminate\Support\Facades\Cache::put('syncing_indiapost_pincodes_query', \$pincode ?: 'ALL', now()->addMinutes(15));
TXT;

$search2 = <<<TXT
                // abort the entire bulk sync immediately to prevent hanging
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync pincode ' . \$code . ': ' . \$e->getMessage(),
                    'errors' => \$errors
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Synced \$syncedCount offices from India Post successfully.",
            'errors' => \$errors
        ]);
    }
TXT;

$replace2 = <<<TXT
                // abort the entire bulk sync immediately to prevent hanging
                \Illuminate\Support\Facades\Cache::forget('syncing_indiapost_pincodes');
                \Illuminate\Support\Facades\Cache::forget('syncing_indiapost_pincodes_query');
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync pincode ' . \$code . ': ' . \$e->getMessage(),
                    'errors' => \$errors
                ], 500);
            }
        }

        \Illuminate\Support\Facades\Cache::forget('syncing_indiapost_pincodes');
        \Illuminate\Support\Facades\Cache::forget('syncing_indiapost_pincodes_query');
        return response()->json([
            'success' => true,
            'message' => "Synced \$syncedCount offices from India Post successfully.",
            'errors' => \$errors
        ]);
    }
TXT;

$content = str_replace($search1, $replace1, $content);
$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Patched Controller successfully\n";
