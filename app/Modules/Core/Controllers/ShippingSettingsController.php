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
        $settings = SystemSetting::where('key', 'like', 'india_post_%')->pluck('value', 'key');

        // Decrypt password if it exists for the frontend (or just leave it empty for security)
        $password = isset($settings['india_post_password']) ? Crypt::decryptString($settings['india_post_password']) : '';

        return view('shipping.settings', compact('settings', 'password'));
    }

    /**
     * Update the India Post shipping settings.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'india_post_base_url' => 'required|url|max:255',
            'india_post_username' => 'required|string|max:255',
            'india_post_password' => 'nullable|string|max:255', // If empty, we don't update it
            'india_post_bulk_customer_id' => 'required|string|max:255',
            'india_post_contract_sp_doc' => 'nullable|string|max:255',
            'india_post_contract_sp_parcel' => 'nullable|string|max:255',
            'india_post_contract_bp' => 'nullable|string|max:255',
            'india_post_contract_24_sp_doc' => 'nullable|string|max:255',
            'india_post_contract_24_spp_parspl' => 'nullable|string|max:255',
            'india_post_contract_48_sp_doc' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            if ($key === 'india_post_password') {
                if (! empty($value)) {
                    SystemSetting::updateOrCreate(
                        ['key' => $key],
                        ['value' => Crypt::encryptString($value)]
                    );
                }
            } else {
                SystemSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }

        return response()->json(['success' => true, 'message' => 'India Post settings updated successfully.']);
    }
}
