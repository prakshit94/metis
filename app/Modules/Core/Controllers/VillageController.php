<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Modules\Catalog\Models\Service;
use App\Modules\Core\Models\Village;
use App\Modules\Core\Models\VillageServiceMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use App\Services\Shipping\Providers\IndiaPostProvider;
use Illuminate\Support\Facades\Log;

class VillageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:village-view', only: ['index', 'show', 'servicesOptions', 'search']),
            new Middleware('permission:village-create', only: ['store', 'import', 'importTemplate']),
            new Middleware('permission:village-edit', only: ['update', 'bulkAction', 'syncIndiaPostPincodes']),
            new Middleware('permission:village-delete', only: ['destroy']),
            new Middleware('permission:village-export', only: ['export', 'exportSelected']),
        ];
    }

    /**
     * Display a listing of villages with pagination, search, sorting and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $sortMap = [
            'id' => 'id',
            'village_name' => 'village_name',
            'pincode' => 'pincode',
            'taluka_name' => 'taluka_name',
            'district_name' => 'district_name',
            'state_name' => 'state_name',
        ];

        $sortBy = $sortMap[$request->input('sort_by', 'id')] ?? 'id';
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $query = Village::query()->with(['services', 'mappings.service']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('village_name', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "{$search}%")
                    ->orWhere('taluka_name', 'like', "%{$search}%")
                    ->orWhere('district_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('state')) {
            $states = array_filter(array_map('trim', explode(',', (string) $request->input('state'))));
            if (! empty($states)) {
                $query->whereIn('state_name', $states);
            }
        }

        if ($request->filled('district')) {
            $districts = array_filter(array_map('trim', explode(',', (string) $request->input('district'))));
            if (! empty($districts)) {
                $query->whereIn('district_name', $districts);
            }
        }

        if ($request->filled('taluka')) {
            $talukas = array_filter(array_map('trim', explode(',', (string) $request->input('taluka'))));
            if (! empty($talukas)) {
                $query->whereIn('taluka_name', $talukas);
            }
        }

        if ($request->filled('village')) {
            $villageNames = array_filter(array_map('trim', explode(',', (string) $request->input('village'))));
            if (! empty($villageNames)) {
                $query->whereIn('village_name', $villageNames);
            }
        }

        if ($request->filled('service_id')) {
            $serviceId = (int) $request->input('service_id');
            $query->whereHas('mappings', function ($q) use ($serviceId): void {
                $q->where('service_id', $serviceId)->where('is_available', true);
            });
        }

        if ($request->filled('deleted')) {
            $deleted = $request->input('deleted');
            if ($deleted === 'with') {
                $query->withTrashed();
            } elseif ($deleted === 'only') {
                $query->onlyTrashed();
            }
        }

        // LOB/State scoping: restrict village browsing to the user's assigned state.
        // Global users (Admin/Super Admin/view-all-data) see all states — lob_state_name returns null.
        $lobStateName = $request->user()?->lob_state_name;
        if ($lobStateName) {
            $query->where('state_name', $lobStateName);
        }

        // Stats calculation
        $statsQuery = clone $query;
        $statsQuery->setEagerLoads([]);
        
        $counts = (clone $statsQuery)->select([
            DB::raw('COUNT(*) as total'),
            DB::raw('COUNT(DISTINCT pincode) as pincodes'),
            DB::raw('COUNT(DISTINCT district_name) as districts_count'),
        ])->toBase()->first();

        $topDistricts = (clone $statsQuery)
            ->select('district_name as name', DB::raw('COUNT(*) as count'))
            ->groupBy('district_name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) use ($counts) {
                return [
                    'name' => $item->name ?: 'Unknown',
                    'count' => (int) $item->count,
                    'percentage' => $counts->total > 0 ? (int) round(($item->count / $counts->total) * 100) : 0,
                ];
            });

        $activeServices = Service::active()->get();
        $serviceDistribution = $activeServices->map(function ($service) use ($statsQuery) {
            $count = (clone $statsQuery)->whereHas('mappings', function ($q) use ($service) {
                $q->where('service_id', $service->id)->where('is_available', true);
            })->count();
            return [
                'name' => $service->name,
                'count' => $count,
            ];
        });

        $stateDistribution = (clone $statsQuery)
            ->select('state_name as name', DB::raw('COUNT(*) as count'))
            ->groupBy('state_name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name ?: 'Unknown',
                    'count' => (int) $item->count,
                ];
            });

        $deliveryDistribution = (clone $statsQuery)
            ->select('delivery_office_flag as name', DB::raw('COUNT(*) as count'))
            ->groupBy('delivery_office_flag')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name ? 'Delivery' : 'Non-Delivery',
                    'count' => (int) $item->count,
                ];
            });

        $officeTypeDistribution = (clone $statsQuery)
            ->select('office_type_code as name', DB::raw('COUNT(*) as count'))
            ->groupBy('office_type_code')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name ?: 'Unknown',
                    'count' => (int) $item->count,
                ];
            });

        $stats = [
            'total' => (int) ($counts->total ?? 0),
            'pincodes' => (int) ($counts->pincodes ?? 0),
            'districts_count' => (int) ($counts->districts_count ?? 0),
            'services' => $activeServices->count(),
            'top_districts' => $topDistricts,
            'service_distribution' => $serviceDistribution,
            'state_distribution' => $stateDistribution,
            'delivery_distribution' => $deliveryDistribution,
            'office_type_distribution' => $officeTypeDistribution,
        ];

        $villages = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        // Include filters lists with caching
        // For LOB users, restrict the state dropdown to their own state
        if ($lobStateName) {
            $statesList = collect([$lobStateName]);
        } else {
            $statesList = Cache::remember('geo_states', 3600, function () {
                return Village::distinct()->pluck('state_name')->filter()->sort()->values();
            });
        }

        $targetStates = $request->filled('state') 
            ? array_map('trim', explode(',', (string) $request->state)) 
            : ($lobStateName ? [$lobStateName] : []);

        $districtsList = Cache::remember('geo_districts_'.md5(implode(',', $targetStates)), 3600, function () use ($targetStates) {
            return Village::when(!empty($targetStates), function ($q) use ($targetStates) {
                $q->whereIn('state_name', $targetStates);
            })->distinct()->pluck('district_name')->filter()->sort()->values();
        });

        $targetDistricts = $request->filled('district') ? array_map('trim', explode(',', (string) $request->district)) : [];
        $talukasList = Cache::remember('geo_talukas_'.md5(implode(',', $targetStates).'_'.implode(',', $targetDistricts)), 3600, function () use ($targetStates, $targetDistricts) {
            return Village::when(!empty($targetStates), function ($q) use ($targetStates) {
                $q->whereIn('state_name', $targetStates);
            })->when(!empty($targetDistricts), function ($q) use ($targetDistricts) {
                $q->whereIn('district_name', $targetDistricts);
            })->distinct()->pluck('taluka_name')->filter()->sort()->values();
        });

        $targetTalukas = $request->filled('taluka') ? array_map('trim', explode(',', (string) $request->taluka)) : [];
        $villagesList = !empty($targetTalukas) ? Cache::remember('geo_villages_'.md5(implode(',', $targetStates).'_'.implode(',', $targetDistricts).'_'.implode(',', $targetTalukas)), 3600, function () use ($targetStates, $targetDistricts, $targetTalukas) {
            return Village::when(!empty($targetStates), function ($q) use ($targetStates) {
                $q->whereIn('state_name', $targetStates);
            })->when(!empty($targetDistricts), function ($q) use ($targetDistricts) {
                $q->whereIn('district_name', $targetDistricts);
            })->whereIn('taluka_name', $targetTalukas)
                ->distinct()->pluck('village_name')->filter()->sort()->values();
        }) : [];

        return response()->json([
            'pagination' => $villages,
            'stats' => $stats,
            'states' => $statesList,
            'districts' => $districtsList,
            'talukas' => $talukasList,
            'villages' => $villagesList,
        ]);
    }

    /**
     * Store a new village.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'village_name' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'string', 'max:10'],
            'post_so_name' => ['nullable', 'string', 'max:255'],
            'taluka_name' => ['nullable', 'string', 'max:255'],
            'district_name' => ['nullable', 'string', 'max:255'],
            'state_name' => ['nullable', 'string', 'max:255'],
            'office_id' => ['nullable', 'string', 'max:255'],
            'office_type_code' => ['nullable', 'string', 'max:255'],
            'delivery_office_flag' => ['nullable', 'boolean'],
            'is_rolled_out' => ['nullable', 'boolean'],
        ]);

        $village = Village::create($validated);

        return response()->json([
            'message' => "Village [{$village->village_name}] created successfully.",
            'data' => $village,
        ], 201);
    }

    /**
     * Display a single village.
     */
    public function show(Village $village): JsonResponse
    {
        return response()->json([
            'data' => $village->load(['services', 'mappings.service']),
        ]);
    }

    /**
     * Update a village.
     */
    public function update(Request $request, Village $village): JsonResponse
    {
        $validated = $request->validate([
            'village_name' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'string', 'max:10'],
            'post_so_name' => ['nullable', 'string', 'max:255'],
            'taluka_name' => ['nullable', 'string', 'max:255'],
            'district_name' => ['nullable', 'string', 'max:255'],
            'state_name' => ['nullable', 'string', 'max:255'],
            'office_id' => ['nullable', 'string', 'max:255'],
            'office_type_code' => ['nullable', 'string', 'max:255'],
            'delivery_office_flag' => ['nullable', 'boolean'],
            'is_rolled_out' => ['nullable', 'boolean'],
        ]);

        $village->update($validated);

        if ($request->has('services')) {
            $services = $request->input('services'); // e.g. [service_id => [is_available => 1, priority => 10, remarks => '...']]
            foreach ($services as $serviceId => $data) {
                if (! empty($data['is_available'])) {
                    VillageServiceMapping::updateOrCreate(
                        ['village_id' => $village->id, 'service_id' => (int) $serviceId],
                        [
                            'is_available' => true,
                            'priority' => (int) ($data['priority'] ?? 0),
                            'remarks' => $data['remarks'] ?? null,
                            'serviceable_from_date' => $data['serviceable_from_date'] ?? null,
                            'serviceable_to_date' => $data['serviceable_to_date'] ?? null,
                        ]
                    );
                } else {
                    VillageServiceMapping::where('village_id', $village->id)
                        ->where('service_id', (int) $serviceId)
                        ->update(['is_available' => false]);
                }
            }
        }

        return response()->json([
            'message' => "Village [{$village->village_name}] updated successfully.",
            'data' => $village->load(['services', 'mappings.service']),
        ]);
    }

    /**
     * Delete a village.
     */
    public function destroy(Village $village): JsonResponse
    {
        $name = $village->village_name;
        $village->delete();

        return response()->json([
            'message' => "Village [{$name}] deleted successfully.",
        ]);
    }

    /**
     * Bulk actions for villages (delete, service-update).
     */
    public function syncIndiaPostPincodes(Request $request, IndiaPostProvider $indiaPostProvider): JsonResponse
    {
        // ── Stop action: write a dedicated stop flag so the running batch loop can
        //    detect it on every pincode iteration, regardless of timing.
        if ($request->input('action') === 'stop') {
            Cache::put('stop_indiapost_sync', true, now()->addMinutes(2));
            Cache::forget('syncing_indiapost_pincodes');
            Cache::forget('syncing_indiapost_pincodes_query');
            return response()->json([
                'success'  => true,
                'finished' => true,
                'stopped'  => true,
                'message'  => 'Stop signal sent. Sync will halt after the current pincode.',
            ]);
        }

        // Honour a queued stop before starting a new batch
        if (Cache::get('stop_indiapost_sync')) {
            Cache::forget('stop_indiapost_sync');
            Cache::forget('syncing_indiapost_pincodes');
            Cache::forget('syncing_indiapost_pincodes_query');
            return response()->json([
                'success'  => true,
                'finished' => true,
                'stopped'  => true,
                'message'  => 'Sync was stopped.',
                'errors'   => [],
            ]);
        }

        $pincode = $request->input('pincode');

        // Mark sync as running (TTL refreshed each batch so stale flags expire automatically)
        Cache::put('syncing_indiapost_pincodes', true, now()->addMinutes(15));
        Cache::put('syncing_indiapost_pincodes_query', $pincode ?: 'ALL', now()->addMinutes(15));

        $pincodesToSync = [];
        if ($pincode) {
            $pincodesToSync[] = $pincode;
        } else {
            $pincodesToSync = Village::where(function ($q) {
                $q->whereNull('office_id')
                  ->orWhereNull('office_type_code')
                  ->orWhereIn('office_type_code', ['INVALID', '']);
            })->select('pincode')->distinct()->pluck('pincode')->toArray();
        }

        // Nothing left to sync — clear flags and return done immediately
        if (empty($pincodesToSync)) {
            Cache::forget('syncing_indiapost_pincodes');
            Cache::forget('syncing_indiapost_pincodes_query');
            return response()->json([
                'success'  => true,
                'finished' => true,
                'message'  => 'All pincodes are already synced. Nothing to do.',
                'errors'   => [],
            ]);
        }

        $syncedCount = 0;
        $errors      = [];
        $startTime   = time();

        foreach ($pincodesToSync as $code) {
            // ── Per-iteration stop check (reads the flag written by a parallel stop request)
            if (Cache::get('stop_indiapost_sync')) {
                Cache::forget('stop_indiapost_sync');
                Cache::forget('syncing_indiapost_pincodes');
                Cache::forget('syncing_indiapost_pincodes_query');
                return response()->json([
                    'success'  => true,
                    'finished' => true,
                    'stopped'  => true,
                    'message'  => "Sync stopped. Processed {$syncedCount} offices before stopping.",
                    'errors'   => $errors,
                ]);
            }

            // ── 20-second batch timeout: return finished:false so the client sends
            //    the next batch automatically. Do NOT clear running flags here.
            if (time() - $startTime > 20) {
                return response()->json([
                    'success'  => true,
                    'finished' => false,
                    'message'  => "Synced {$syncedCount} offices in this batch. Continuing next batch...",
                    'errors'   => $errors,
                ]);
            }

            try {
                if (method_exists($indiaPostProvider, 'getPincodeDetails')) {
                    $details = $indiaPostProvider->getPincodeDetails((string) $code);
                } else {
                    // Fallback for OPcache/workers where the new method is not yet loaded
                    $token    = $indiaPostProvider->authenticate();
                    $settings = \App\Models\SystemSetting::where('key', 'like', 'india_post_%')->pluck('value', 'key');
                    $baseUrl  = $settings['india_post_base_url'] ?? config('shipping.providers.india_post.base_url');
                    $baseUrl  = str_replace('beextcustomer', 'bemasterdata', $baseUrl);

                    $response = \Illuminate\Support\Facades\Http::withToken($token)
                        ->withOptions(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]])
                        ->get("{$baseUrl}/v1/offices/limited-details", [
                            'pincode'     => (string) $code,
                            'limit'       => 50,
                            'office-type' => 'post',
                        ]);

                    if ($response->successful()) {
                        $details = $response->json();
                    } else {
                        throw new \Exception('Failed to fetch pincode details: ' . $response->body());
                    }
                }

                $hasValidData = false;
                $officesList  = isset($details['data']) && is_array($details['data']) ? $details['data'] : $details;

                if (is_array($officesList) && count($officesList) > 0) {
                    foreach ($officesList as $office) {
                        if (! is_array($office)) {
                            continue;
                        }

                        $hasValidData = true;
                        $villageName  = ! empty($office['village_name']) && $office['village_name'] !== 'Choose an option'
                            ? $office['village_name']
                            : ($office['office_name'] ?? 'Unknown');

                        $existingVillage = Village::where('pincode', $code)
                            ->where('office_id', $office['office_id'] ?? null)
                            ->first();

                        if (! $existingVillage) {
                            // Adopt an un-synced stub record to avoid orphaned duplicates
                            $existingVillage = Village::where('pincode', $code)
                                ->where(function ($q) {
                                    $q->whereNull('office_id')
                                      ->orWhereNull('office_type_code')
                                      ->orWhereIn('office_type_code', ['INVALID', 'FAILED', 'API_ERROR', '']);
                                })
                                ->first();
                        }

                        $updateData = [
                            'village_name'         => $villageName,
                            'post_so_name'         => $office['office_name'] ?? null,
                            'taluka_name'          => $office['taluk_name'] ?? null,
                            'district_name'        => $office['city_name'] ?? null,
                            'state_name'           => $office['state_name'] ?? null,
                            'office_type_code'     => $office['office_type_code'] ?? null,
                            'delivery_office_flag' => $office['delivery_office_flag'] ?? false,
                            'is_rolled_out'        => $office['is_rolled_out'] ?? false,
                        ];

                        if ($existingVillage) {
                            $updateData['office_id'] = $office['office_id'] ?? null;
                            $existingVillage->update($updateData);
                        } else {
                            $updateData['pincode']   = $office['pincode'] ?? $code;
                            $updateData['office_id'] = $office['office_id'] ?? null;
                            Village::create($updateData);
                        }

                        $syncedCount++;
                    }
                }

                // Mark pincodes with no valid offices so they are never retried
                if (! $hasValidData) {
                    Village::where('pincode', $code)
                           ->where(function ($q) {
                               $q->whereNull('office_type_code')
                                 ->orWhere('office_type_code', 'INVALID');
                           })
                           ->update(['office_type_code' => 'FAILED']);
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync India Post pincode {$code}: " . $e->getMessage());

                Village::where('pincode', $code)
                       ->where(function ($q) {
                           $q->whereNull('office_type_code')
                             ->orWhereIn('office_type_code', ['INVALID', 'FAILED', 'API_ERROR']);
                       })
                       ->update(['office_type_code' => 'API_ERROR']);

                $errors[] = $code;

                // Network or Auth errors
                if (
                    $e instanceof \Illuminate\Http\Client\ConnectionException
                    || str_contains(strtolower($e->getMessage()), 'curl')
                    || str_contains(strtolower($e->getMessage()), 'authenticate')
                    || str_contains(strtolower($e->getMessage()), 'unauthorized')
                    || str_contains(strtolower($e->getMessage()), 'timeout')
                ) {
                    Cache::forget('india_post_access_token'); // Clear token to force re-auth next time

                    // Instead of aborting with a 500 error, we tell the frontend to
                    // automatically continue to the next batch. The broken token is cleared,
                    // so the next batch will fetch a fresh one without user interaction.
                    return response()->json([
                        'success'  => true,
                        'finished' => false,
                        'message'  => 'Auth/Network timeout. Re-authenticating and continuing automatically...',
                        'errors'   => $errors,
                    ]);
                }
            }
        }

        // ── All pincodes processed — clear all running/stop flags
        Cache::forget('syncing_indiapost_pincodes');
        Cache::forget('syncing_indiapost_pincodes_query');
        Cache::forget('stop_indiapost_sync');

        $errorSuffix = count($errors) > 0
            ? ' Errors on ' . count($errors) . ' pincode(s): ' . implode(', ', $errors)
            : '';

        return response()->json([
            'success'  => true,
            'finished' => true,
            'message'  => "Sync complete. Synced {$syncedCount} offices from India Post.{$errorSuffix}",
            'errors'   => $errors,
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:delete,service-update,restore,force-delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:villages,id'],
            'service_id' => ['required_if:action,service-update', 'nullable', 'integer', 'exists:services,id'],
            'status' => ['required_if:action,service-update', 'nullable', 'string', 'in:available,unavailable'],
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];

        if ($action === 'delete') {
            abort_unless($request->user()?->can('village-delete'), 403);
            Village::whereIn('id', $ids)->get()->each->delete();

            return response()->json([
                'message' => count($ids).' village(s) deleted successfully.',
                'deleted' => $ids,
            ]);
        }

        if ($action === 'restore') {
            abort_unless($request->user()?->can('village-restore'), 403);
            Village::withTrashed()->whereIn('id', $ids)->get()->each->restore();

            return response()->json([
                'message' => count($ids).' village(s) restored successfully.',
                'restored' => $ids,
            ]);
        }

        if ($action === 'force-delete') {
            abort_unless($request->user()?->can('village-permanent-delete'), 403);
            Village::withTrashed()->whereIn('id', $ids)->get()->each->forceDelete();

            return response()->json([
                'message' => count($ids).' village(s) permanently deleted.',
                'deleted' => $ids,
            ]);
        }

        if ($action === 'service-update') {
            $serviceId = (int) $validated['service_id'];
            $isAvailable = $validated['status'] === 'available';

            $mappings = [];
            foreach ($ids as $id) {
                $mappings[] = [
                    'village_id' => $id,
                    'service_id' => $serviceId,
                    'is_available' => $isAvailable,
                    'serviceable_from_date' => null,
                    'serviceable_to_date' => null,
                    'remarks' => 'Bulk updated via admin',
                    'priority' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Using upsert
            DB::table('village_service_mappings')->upsert(
                $mappings,
                ['village_id', 'service_id'],
                ['is_available', 'remarks', 'updated_at']
            );

            return response()->json([
                'message' => 'Service status updated successfully for '.count($ids).' village(s).',
                'ids' => $ids,
            ]);
        }

        return response()->json(['message' => 'Invalid bulk action.'], 422);
    }

    /**
     * Options for service filter dropdown.
     */
    public function servicesOptions(): JsonResponse
    {
        $services = Service::active()->get();

        return response()->json($services);
    }

    /**
     * Search/Autocomplete endpoint for villages.
     */
    public function search(Request $request): JsonResponse
    {
        if (! $request->filled('q') || strlen((string) $request->input('q')) < 3) {
            return response()->json(['data' => []]);
        }

        $term = (string) $request->input('q');
        $query = Village::search($term)
            ->with(['services' => function ($q) {
                $q->where('is_active', true)
                  ->where('village_service_mappings.is_available', true);
            }]);

        // LOB/State scoping: restrict autocomplete to the user's state
        if ($lobStateName = $request->user()?->lob_state_name) {
            $query->where('state_name', $lobStateName);
        }

        $villages = $query->limit(30)->get();

        return response()->json(['data' => $villages]);
    }

    /**
     * Import villages via CSV file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'preview' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        // Check if just previewing
        if ($request->input('preview')) {
            $rows      = [];
            $totalRows = 0;
            // Cap the number of rows sent in the JSON response to prevent PHP
            // memory exhaustion on very large CSVs. 1000 rows is more than
            // enough for a meaningful preview; the actual import processes every row.
            $previewLimit = 1000;

            $handle = fopen($path, 'r');
            if ($handle) {
                $header = fgetcsv($handle);
                while (($line = fgetcsv($handle)) !== false) {
                    // Skip completely blank lines (common at end of CSV exports)
                    if (count(array_filter($line, fn ($v) => trim($v) !== '')) === 0) {
                        continue;
                    }
                    $totalRows++;
                    if (count($rows) < $previewLimit) {
                        $rows[] = array_combine(
                            array_slice(array_pad($header, count($line), ''), 0, count($line)),
                            $line
                        );
                    }
                }
                fclose($handle);
            }

            return response()->json([
                'preview'   => true,
                'rows'      => $rows,
                'total'     => $totalRows,
                'truncated' => $totalRows > $previewLimit,
            ]);
        }

        DB::transaction(function () use ($path): void {
            LazyCollection::make(function () use ($path) {
                $handle = fopen($path, 'r');
                if ($handle) {
                    fgetcsv($handle); // skip header
                    while (($line = fgetcsv($handle)) !== false) {
                        yield $line;
                    }
                    fclose($handle);
                }
            })
                ->chunk(1000)
                ->each(function ($chunk): void {
                    $data = $chunk->map(function ($row) {
                        if (count($row) < 2) {
                            return null;
                        }

                        return [
                            'village_name' => $row[0],
                            'normalized_name' => strtolower(trim($row[0])),
                            'pincode' => $row[1],
                            'post_so_name' => ($row[2] ?? null) === '#N/A' ? null : ($row[2] ?? null),
                            'taluka_name' => ($row[3] ?? null) === '#N/A' ? null : ($row[3] ?? null),
                            'district_name' => ($row[4] ?? null) === '#N/A' ? null : ($row[4] ?? null),
                            'state_name' => ($row[5] ?? null) === '#N/A' ? null : ($row[5] ?? null),
                            'office_id' => ($row[6] ?? null) === '#N/A' ? null : ($row[6] ?? null),
                            'office_type_code' => ($row[7] ?? null) === '#N/A' ? null : ($row[7] ?? null),
                            'delivery_office_flag' => strtolower((string)($row[8] ?? '')) === 'yes' || strtolower((string)($row[8] ?? '')) === 'true' || ($row[8] ?? null) == 1,
                            'is_rolled_out' => strtolower((string)($row[9] ?? '')) === 'yes' || strtolower((string)($row[9] ?? '')) === 'true' || ($row[9] ?? null) == 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->filter()->values()->toArray();

                    if (! empty($data)) {
                        DB::table('villages')->insert($data);
                    }
                });
        });

        return response()->json([
            'message' => 'Villages imported successfully.',
        ]);
    }

    /**
     * Download CSV template for import.
     */
    public function importTemplate()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['village_name', 'pincode', 'post_so_name', 'taluka_name', 'district_name', 'state_name', 'office_id', 'office_type_code', 'delivery_office_flag', 'is_rolled_out']);
            fputcsv($out, ['Kawatha', '440001', 'Nagpur SO', 'Kamptee', 'Nagpur', 'Maharashtra', '1234', 'PO', 'Yes', 'Yes']);
            fclose($out);
        }, 'villages-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Export all villages (with filters) to CSV.
     */
    public function export(Request $request)
    {
        $query = Village::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('village_name', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "{$search}%")
                    ->orWhere('taluka_name', 'like', "%{$search}%")
                    ->orWhere('district_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('state')) {
            $states = array_filter(array_map('trim', explode(',', (string) $request->input('state'))));
            if (! empty($states)) {
                $query->whereIn('state_name', $states);
            }
        }

        if ($request->filled('district')) {
            $districts = array_filter(array_map('trim', explode(',', (string) $request->input('district'))));
            if (! empty($districts)) {
                $query->whereIn('district_name', $districts);
            }
        }

        if ($request->filled('taluka')) {
            $talukas = array_filter(array_map('trim', explode(',', (string) $request->input('taluka'))));
            if (! empty($talukas)) {
                $query->whereIn('taluka_name', $talukas);
            }
        }

        if ($request->filled('village')) {
            $villageNames = array_filter(array_map('trim', explode(',', (string) $request->input('village'))));
            if (! empty($villageNames)) {
                $query->whereIn('village_name', $villageNames);
            }
        }

        if ($request->filled('service_id')) {
            $serviceId = (int) $request->input('service_id');
            $query->whereHas('mappings', function ($q) use ($serviceId): void {
                $q->where('service_id', $serviceId)->where('is_available', true);
            });
        }

        // LOB/State scoping: restrict export to the user's assigned state
        if ($lobStateName = $request->user()?->lob_state_name) {
            $query->where('state_name', $lobStateName);
        }

        if ($request->filled('deleted')) {
            $deleted = $request->input('deleted');
            if ($deleted === 'with') {
                $query->withTrashed();
            } elseif ($deleted === 'only') {
                $query->onlyTrashed();
            }
        }

        $villages = $query->with(['mappings.service'])->get();
        $filename = 'villages-export-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload($this->generateCsvExportCallback($villages), $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Export selected villages to CSV.
     */
    public function exportSelected(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
        ]);

        $villages = Village::withTrashed()->with(['mappings.service'])->whereIn('id', $validated['ids'])->get();
        $filename = 'villages-export-selected-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload($this->generateCsvExportCallback($villages), $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function generateCsvExportCallback($villages)
    {
        return function () use ($villages) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Village Name',
                'Pincode',
                'Post SO Name',
                'Taluka',
                'District',
                'State',
                'Office ID',
                'Office Type',
                'Delivery Office',
                'Rolled Out',
                'Mapped Services',
                'Available Services Count',
                'Status',
            ]);

            foreach ($villages as $village) {
                $availableMappings = $village->mappings
                    ->where('is_available', true)
                    ->sortBy('priority');

                $serviceNames = $availableMappings
                    ->map(fn ($m) => $m->service?->name)
                    ->filter()
                    ->implode(', ');

                fputcsv($file, [
                    $village->id,
                    $village->village_name,
                    $village->pincode,
                    $village->post_so_name,
                    $village->taluka_name,
                    $village->district_name,
                    $village->state_name,
                    $village->office_id ?: '—',
                    $village->office_type_code ?: '—',
                    $village->delivery_office_flag ? 'Yes' : 'No',
                    $village->is_rolled_out ? 'Yes' : 'No',
                    $serviceNames ?: 'None',
                    $availableMappings->count(),
                    $village->trashed() ? 'Deleted' : 'Active',
                ]);
            }

            fclose($file);
        };
    }
}
