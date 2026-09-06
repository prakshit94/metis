<?php

namespace App\Modules\Core\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ShippingSettingsController extends Controller
{
    /**
     * Display the shipping settings view.
     */
    public function index()
    {
        $settings = SystemSetting::where('key', 'india_post_offices')->pluck('value', 'key');
        
        // Scrub passwords before sending to frontend
        $offices = isset($settings['india_post_offices']) ? json_decode($settings['india_post_offices'], true) : [];
        if (is_array($offices)) {
            foreach ($offices as &$office) {
                $office['api_password'] = '';
            }
            $settings['india_post_offices'] = json_encode($offices);
        }

        return view('shipping.settings', compact('settings'));
    }

    /**
     * Update the India Post shipping settings.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'india_post_offices' => 'nullable|array',
        ]);

        $offices = $validated['india_post_offices'] ?? [];
        
        // Fetch existing to retain passwords if new one is empty
        $existingSettings = SystemSetting::where('key', 'india_post_offices')->first();
        $existingOffices = $existingSettings ? json_decode($existingSettings->value, true) : [];
        if (!is_array($existingOffices)) {
            $existingOffices = [];
        }
        $existingOfficesMap = collect($existingOffices)->keyBy('id');

        foreach ($offices as &$office) {
            if (!empty($office['api_password'])) {
                $office['api_password'] = Crypt::encryptString($office['api_password']);
            } else {
                // Keep old password if it exists
                if (isset($existingOfficesMap[$office['id']]['api_password'])) {
                    $office['api_password'] = $existingOfficesMap[$office['id']]['api_password'];
                }
            }
        }
        
        SystemSetting::updateOrCreate(
            ['key' => 'india_post_offices'],
            ['value' => json_encode($offices)]
        );

        return response()->json(['success' => true, 'message' => 'India Post settings updated successfully.']);
    }
}