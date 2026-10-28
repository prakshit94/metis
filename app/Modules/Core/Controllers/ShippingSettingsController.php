<?php

namespace App\Modules\Core\Controllers;

use App\Models\IndiaPostAwbTracking;
use App\Models\IndiaPostBarcodeRange;
use App\Models\SystemSetting;
use App\Services\BusinessSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

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
            $offices = array_values(array_filter($offices, 'is_array'));
            foreach ($offices as &$office) {
                $office['api_password'] = '';

                // Calculate AWB Statistics from DB instead of JSON config
                $activeRange = IndiaPostBarcodeRange::where('office_id', $office['id'] ?? '')
                    ->where('status', 'active')
                    ->first();

                if ($activeRange) {
                    $start = $activeRange->start_sequence;
                    $end = $activeRange->end_sequence;
                    $current = $activeRange->current_sequence;

                    $office['awb_stats'] = [
                        'total' => max(0, $end - $start + 1),
                        'used' => max(0, $current - $start),
                        'remaining' => max(0, $end - $current),
                        'prefix' => $activeRange->prefix,
                        'start_sequence' => $start,
                        'end_sequence' => $end,
                        'current_sequence' => $current,
                    ];
                } else {
                    $office['awb_stats'] = [
                        'total' => 0,
                        'used' => 0,
                        'remaining' => 0,
                        'prefix' => null,
                        'start_sequence' => null,
                        'end_sequence' => null,
                        'current_sequence' => null,
                    ];
                }
            }
        } else {
            $offices = [];
        }

        if (request()->is('api/*')) {
            return response()->json(['offices' => $offices, 'default_base_url' => config('shipping.providers.india_post.base_url')]);
        }

        return view('shipping.settings', [
            'offices' => $offices,
            'defaultIndiaPostBaseUrl' => config('shipping.providers.india_post.base_url'),
            'isProduction' => app()->environment('production'),
        ]);
    }

    /**
     * Update the India Post shipping settings.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'india_post_offices' => 'nullable|array|max:100',
            'india_post_offices.*' => 'required|array',
            'india_post_offices.*.id' => 'required|string|max:100|distinct',
            'india_post_offices.*.status' => 'required|in:active,inactive',
            'india_post_offices.*.is_default' => 'sometimes|boolean',
            'india_post_offices.*.api_base_url' => 'nullable|url:http,https|max:2048',
            'india_post_offices.*.api_username' => 'nullable|string|max:255',
            'india_post_offices.*.api_password' => 'nullable|string|max:1024',
        ]);

        $offices = $validated['india_post_offices'] ?? [];
        foreach ($offices as $office) {
            foreach ($office as $key => $value) {
                if (! is_null($value) && ! is_scalar($value)) {
                    throw ValidationException::withMessages(['india_post_offices' => 'Office fields must be scalar values.']);
                }
            }
        }

        // Fetch existing to retain passwords if new one is empty
        $existingSettings = SystemSetting::where('key', 'india_post_offices')->first();
        $existingOffices = $existingSettings ? json_decode($existingSettings->value, true) : [];
        if (! is_array($existingOffices)) {
            $existingOffices = [];
        }
        $existingOfficesMap = collect($existingOffices)->keyBy('id');

        foreach ($offices as &$office) {
            if (! empty($office['api_password'])) {
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

    public function awbLogs(string $officeId)
    {
        $logs = IndiaPostAwbTracking::where('office_id', $officeId)
            ->orderBy('id', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    /**
     * Get barcode ranges for an office.
     */
    public function getRanges(string $officeId)
    {
        $ranges = IndiaPostBarcodeRange::withTrashed()
            ->where('office_id', $officeId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'ranges' => $ranges,
        ]);
    }

    public function deleteRange($rangeId)
    {
        $officeId = IndiaPostBarcodeRange::withTrashed()->findOrFail($rangeId)->office_id;

        return app(BusinessSequence::class)->run('awb:allocation', function () use ($rangeId) {
            $range = IndiaPostBarcodeRange::withTrashed()->findOrFail($rangeId);

            if ($range->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete an active range.',
                ], 422);
            }

            $range->delete(); // Soft delete

            return response()->json([
                'success' => true,
                'message' => 'Barcode range deleted successfully.',
            ]);
        });
    }

    public function updateRange(Request $request, $rangeId)
    {
        $officeId = IndiaPostBarcodeRange::findOrFail($rangeId)->office_id;

        return app(BusinessSequence::class)->run('awb:allocation', function () use ($request, $rangeId) {
            $range = IndiaPostBarcodeRange::findOrFail($rangeId);

            $validated = $request->validate([
                'prefix' => ['required', 'regex:/^[A-Z]{2}$/i'],
                'start_sequence' => 'required|integer|min:0|max:99999999',
                'end_sequence' => 'required|integer|gt:start_sequence|max:99999999',
            ]);

            $overlap = IndiaPostBarcodeRange::withTrashed()->where('prefix', strtoupper($validated['prefix']))->whereKeyNot($rangeId)
                ->where('start_sequence', '<=', $validated['end_sequence'])->where('end_sequence', '>=', $validated['start_sequence'])->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['start_sequence' => 'This barcode range overlaps an existing assignment.']);
            }
            if ($validated['end_sequence'] < $range->current_sequence) {
                return response()->json([
                    'success' => false,
                    'message' => 'End sequence cannot be less than the currently used sequence ('.$range->current_sequence.').',
                ], 422);
            }

            if (IndiaPostAwbTracking::where('range_id', $range->id)->exists()
                && (strtoupper($validated['prefix']) !== $range->prefix || (int) $validated['start_sequence'] !== (int) $range->start_sequence)) {
                throw ValidationException::withMessages(['prefix' => 'The prefix and start of a used barcode range cannot be changed.']);
            }
            $newCurrentSequence = $range->current_sequence;
            if ($newCurrentSequence < $validated['start_sequence']) {
                $newCurrentSequence = $validated['start_sequence'];
            }

            $status = $range->status;
            if ($status === 'exhausted' && $newCurrentSequence <= $validated['end_sequence']) {
                $status = 'queued';
            }

            $range->update([
                'prefix' => strtoupper($validated['prefix']),
                'start_sequence' => $validated['start_sequence'],
                'end_sequence' => $validated['end_sequence'],
                'current_sequence' => $newCurrentSequence,
                'status' => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Barcode range updated successfully.',
            ]);
        });
    }

    /**
     * Store a new barcode range for an office.
     */
    public function storeRange(Request $request, string $officeId)
    {
        return app(BusinessSequence::class)->run('awb:allocation', function () use ($request, $officeId) {
            $validated = $request->validate([
                'prefix' => ['required', 'regex:/^[A-Z]{2}$/i'],
                'start_sequence' => 'required|integer|min:0|max:99999999',
                'end_sequence' => 'required|integer|gt:start_sequence|max:99999999',
            ]);

            $overlap = IndiaPostBarcodeRange::withTrashed()->where('prefix', strtoupper($validated['prefix']))
                ->where('start_sequence', '<=', $validated['end_sequence'])->where('end_sequence', '>=', $validated['start_sequence'])->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['start_sequence' => 'This barcode range overlaps an existing assignment.']);
            }
            $hasActive = IndiaPostBarcodeRange::where('office_id', $officeId)
                ->where('status', 'active')
                ->exists();

            $range = IndiaPostBarcodeRange::create([
                'office_id' => $officeId,
                'prefix' => strtoupper($validated['prefix']),
                'start_sequence' => $validated['start_sequence'],
                'end_sequence' => $validated['end_sequence'],
                'current_sequence' => $validated['start_sequence'],
                'status' => $hasActive ? 'queued' : 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Barcode range added successfully.',
                'range' => $range,
            ]);
        });
    }

    public function activateRange($rangeId)
    {
        $officeId = IndiaPostBarcodeRange::findOrFail($rangeId)->office_id;

        return app(BusinessSequence::class)->run('awb:allocation', function () use ($rangeId) {
            $range = IndiaPostBarcodeRange::findOrFail($rangeId);

            if ($range->status === 'exhausted' || $range->current_sequence > $range->end_sequence) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot activate a range that is completely utilized (exhausted).',
                ], 422);
            }

            // Find if there is currently an active range for this office
            $activeRange = IndiaPostBarcodeRange::where('office_id', $range->office_id)
                ->where('status', 'active')
                ->first();

            if ($activeRange && $activeRange->id !== $range->id) {
                // Demote the current active range back to queued
                $activeRange->update(['status' => 'queued']);
            }

            // Set the requested range to active
            $range->update(['status' => 'active']);

            return response()->json([
                'success' => true,
                'message' => 'Barcode range activated successfully.',
            ]);
        });
    }
}
