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
            new Middleware('permission:village-create', only: ['store']),
            new Middleware('permission:village-import', only: ['import', 'importTemplate']),
            new Middleware('permission:village-edit', only: ['update', 'syncIndiaPostPincodes']),
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
        $inputPerPage = $request->input('per_page', 15);
        $perPage = ($inputPerPage === 'all' || $inputPerPage == -1) ? -1 : min(max((int) $inputPerPage, 1), 200);

        $query = Village::query()->with(['services', 'mappings.service']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('village_name', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "{$search}%")
                    ->orWhere('taluka_name', 'like', "%{$search}%")
                    ->orWhere('district_name', 'like', "%{$search}%")
                    ->orWhere('office_type_code', 'like', "%{$search}%");
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
            if ($request->input('service_id') === 'unassigned') {
                $query->whereDoesntHave('mappings', function ($q): void {
                    $q->where('is_available', true);
                });
            } else {
                $serviceId = (int) $request->input('service_id');
                $query->whereHas('mappings', function ($q) use ($serviceId): void {
                    $q->where('service_id', $serviceId)->where('is_available', true);
                });
            }
        }

        if ($request->filled('office_type_code')) {
            $officeTypes = array_filter(array_map('trim', explode(',', (string) $request->input('office_type_code'))));
            if (! empty($officeTypes)) {
                $query->whereIn('office_type_code', $officeTypes);
            }
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

        if ($perPage === -1) {
            $perPage = max((int) ($counts->total ?? 0), 1);
        }
        $villages = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        // Include filters lists with caching
        // For LOB users, restrict the state dropdown to their own state
        if ($lobStateName) {
            $statesList = collect([$lobStateName]);
        } else {
            $statesList = Village::distinct()->pluck('state_name')->filter()->sort()->values();
        }

        $targetStates = $lobStateName
            ? [$lobStateName]
            : ($request->filled('state') ? array_map('trim', explode(',', (string) $request->state)) : []);

        $districtsList = Village::when($lobStateName || !empty($targetStates), function ($q) use ($targetStates, $lobStateName) {
            if ($lobStateName) {
                $q->where('state_name', $lobStateName);
            } elseif (!empty($targetStates)) {
                $q->whereIn('state_name', $targetStates);
            }
        })->distinct()->pluck('district_name')->filter()->sort()->values();

        $targetDistricts = $request->filled('district') ? array_map('trim', explode(',', (string) $request->district)) : [];
        $talukasList = Village::when($lobStateName || !empty($targetStates), function ($q) use ($targetStates, $lobStateName) {
            if ($lobStateName) {
                $q->where('state_name', $lobStateName);
            } elseif (!empty($targetStates)) {
                $q->whereIn('state_name', $targetStates);
            }
        })->when(!empty($targetDistricts), function ($q) use ($targetDistricts) {
            $q->whereIn('district_name', $targetDistricts);
        })->distinct()->pluck('taluka_name')->filter()->sort()->values();

        $targetTalukas = $request->filled('taluka') ? array_map('trim', explode(',', (string) $request->taluka)) : [];
        $villagesList = !empty($targetTalukas) ? Village::when($lobStateName || !empty($targetStates), function ($q) use ($targetStates, $lobStateName) {
            if ($lobStateName) {
                $q->where('state_name', $lobStateName);
            } elseif (!empty($targetStates)) {
                $q->whereIn('state_name', $targetStates);
            }
        })->when(!empty($targetDistricts), function ($q) use ($targetDistricts) {
            $q->whereIn('district_name', $targetDistricts);
        })->whereIn('taluka_name', $targetTalukas)
            ->distinct()->pluck('village_name')->filter()->sort()->values() : [];

        $officeTypesList = Village::when($lobStateName, fn ($q) => $q->where('state_name', $lobStateName))
            ->distinct()->pluck('office_type_code')->filter()->sort()->values();

        return response()->json([
            'pagination' => $villages,
            'stats' => $stats,
            'states' => $statesList,
            'districts' => $districtsList,
            'talukas' => $talukasList,
            'villages' => $villagesList,
            'office_types' => $officeTypesList,
        ]);
    }

    /**
     * Store a new village.
     */
    public function store(Request $request, IndiaPostProvider $indiaPostProvider): JsonResponse
    {
        $validated = $request->validate([
            'village_name' => ['nullable', 'string', 'max:255'],
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

        $offices = [];
        if (preg_match('/^\d{6}$/', $validated['pincode'])) {
            try {
                $offices = $this->indiaPostOfficesForPincode($validated['pincode'], $indiaPostProvider);
                if ($lobStateName = $request->user()?->lob_state_name) {
                    $offices = array_values(array_filter($offices, fn (array $office) =>
                        strcasecmp((string) $office['state_name'], $lobStateName) === 0
                    ));
                }
            } catch (\Throwable $exception) {
                Log::warning('India Post village auto-import failed.', [
                    'pincode' => $validated['pincode'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($offices === []) {
            if (blank($validated['village_name'] ?? null)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'village_name' => 'No matching India Post records were found. Enter a village name to create the village manually.',
                ]);
            }

            $village = Village::create($validated);

            return response()->json([
                'message' => "Village [{$village->village_name}] created successfully.",
                'data' => $village,
            ], 201);
        }

        $createdVillages = [];
        $existingCount = 0;
        DB::transaction(function () use ($offices, &$createdVillages, &$existingCount): void {
            $processedVillageIds = [];
            foreach ($offices as $officeData) {
                $existingQuery = Village::where('pincode', $officeData['pincode']);
                if ($officeData['office_id']) {
                    $existingQuery->where('office_id', $officeData['office_id']);
                } else {
                    $existingQuery->where('normalized_name', strtolower(trim($officeData['village_name'])));
                    if ($officeData['post_so_name']) {
                        $existingQuery->where('post_so_name', $officeData['post_so_name']);
                    }
                }
                $existing = $existingQuery->first();

                if ($existing) {
                    $existingCount++;
                    $processedVillageIds[] = (int) $existing->id;
                    continue;
                }

                // Reuse an existing unsynced stub instead of creating a duplicate.
                $stub = Village::where('pincode', $officeData['pincode'])
                    ->where(function ($query): void {
                        $query->whereNull('office_id')
                            ->orWhereNull('office_type_code')
                            ->orWhereIn('office_type_code', ['INVALID', 'FAILED', 'API_ERROR', '']);
                    })
                    ->when($processedVillageIds !== [], fn ($query) => $query->whereNotIn('id', $processedVillageIds))
                    ->first();

                if ($stub) {
                    $stub->update($officeData);
                    $savedVillage = $stub->fresh();
                } else {
                    $savedVillage = Village::create($officeData);
                }
                $createdVillages[] = $savedVillage;
                $processedVillageIds[] = (int) $savedVillage->id;
            }
        });

        $createdCount = count($createdVillages);
        $message = $createdCount > 0
            ? "Added {$createdCount} India Post office record(s) for pincode {$validated['pincode']}."
            : "All matching India Post office records for pincode {$validated['pincode']} already exist; no duplicates were added.";

        return response()->json([
            'message' => $message,
            'data' => $createdVillages,
            'created' => $createdCount,
            'already_existing' => $existingCount,
        ], 201);
    }

    /**
     * Display a single village.
     */
    public function show(Request $request, Village $village): JsonResponse
    {
        $this->assertVillageStateAccess($village, $request);

        return response()->json([
            'data' => $village->load(['services', 'mappings.service']),
        ]);
    }

    /**
     * Update a village.
     */
    public function update(Request $request, Village $village): JsonResponse
    {
        $this->assertVillageStateAccess($village, $request);

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
            'services' => ['sometimes', 'array'],
            'services.*' => ['array'],
            'services.*.is_available' => ['required', 'boolean'],
            'services.*.priority' => ['nullable', 'integer', 'min:1'],
            'services.*.remarks' => ['nullable', 'string', 'max:500'],
            'services.*.serviceable_from_date' => ['nullable', 'date'],
            'services.*.serviceable_to_date' => ['nullable', 'date', 'after_or_equal:services.*.serviceable_from_date'],
        ]);

        if (isset($validated['services'])) {
            $serviceIds = array_map('intval', array_keys($validated['services']));
            $validServiceIds = Service::whereIn('id', $serviceIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (count(array_unique($validServiceIds)) !== count(array_unique($serviceIds))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'services' => 'One or more selected services are invalid.',
                ]);
            }
        }

        DB::transaction(function () use ($village, $validated): void {
            $village->update(collect($validated)->except('services')->all());

            if (!isset($validated['services'])) {
                return;
            }
            $services = $validated['services'];
            foreach ($services as $serviceId => $data) {
                if (! empty($data['is_available'])) {
                    VillageServiceMapping::updateOrCreate(
                        ['village_id' => $village->id, 'service_id' => (int) $serviceId],
                        [
                            'is_available' => true,
                            'priority' => (int) ($data['priority'] ?? 1),
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
        });

        $village->refresh();

        return response()->json([
            'message' => "Village [{$village->village_name}] updated successfully.",
            'data' => $village->load(['services', 'mappings.service']),
        ]);
    }

    /**
     * Delete a village.
     */
    public function destroy(Request $request, Village $village): JsonResponse
    {
        $this->assertVillageStateAccess($village, $request);

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
            $hasVillageRecords = Village::where('pincode', $pincode)->exists();
            $hasUnsyncedVillages = Village::where('pincode', $pincode)
                ->where($this->unsyncedVillageCondition())
                ->exists();

            // Keep support for importing a pincode that has no local records, but
            // never call India Post again when every local office is already synced.
            if (!$hasVillageRecords || $hasUnsyncedVillages) {
                $pincodesToSync[] = $pincode;
            }
        } else {
            $pincodesToSync = Village::where($this->unsyncedVillageCondition())
                ->select('pincode')->distinct()->pluck('pincode')->toArray();
        }

        // Nothing left to sync — clear flags and return done immediately
        if (empty($pincodesToSync)) {
            Cache::forget('syncing_indiapost_pincodes');
            Cache::forget('syncing_indiapost_pincodes_query');
            return response()->json([
                'success'  => true,
                'finished' => true,
                'message'  => $pincode
                    ? "Pincode {$pincode} has no pending sync records. Nothing to do."
                    : 'All pincodes are already synced. Nothing to do.',
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

            // A preceding batch or another request may have completed this
            // pincode after the candidate list was built.
            $hasVillageRecords = Village::where('pincode', $code)->exists();
            if ($hasVillageRecords && !Village::where('pincode', $code)
                ->where($this->unsyncedVillageCondition())
                ->exists()) {
                continue;
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

                $officesList = $this->indiaPostOfficeList($details);
                $hasValidData = false;
                $hasInvalidData = false;

                if (is_array($officesList) && count($officesList) > 0) {
                    foreach ($officesList as $office) {
                        if (! is_array($office)) {
                            $hasInvalidData = true;
                            Log::warning('India Post returned a non-object village office record.', [
                                'pincode' => (string) $code,
                            ]);
                            continue;
                        }

                        $officeData = $this->normalizeIndiaPostOffice($office, (string) $code);
                        $officeId = $officeData['office_id'];
                        $officePincode = $officeData['pincode'];
                        if ($officePincode !== (string) $code || empty($office['office_type_code']) || empty($officeId)) {
                            $hasInvalidData = true;
                            Log::warning('India Post returned an incomplete or mismatched village office record.', [
                                'requested_pincode' => (string) $code,
                                'returned_pincode' => $officePincode,
                                'office_id_present' => !empty($officeId),
                                'office_type_present' => !empty($office['office_type_code']),
                            ]);
                        }
                        if ($officePincode !== (string) $code) {
                            $hasInvalidData = true;
                            continue;
                        }
                        $villageName = $officeData['village_name'];
                        if (!is_string($villageName) || trim($villageName) === '') {
                            $hasInvalidData = true;
                            Log::warning('India Post returned an office without a usable village name.', [
                                'pincode' => (string) $code,
                                'office_id' => $officeId,
                            ]);
                            continue;
                        }

                        if (trim((string) $villageName) === '') {
                            $hasInvalidData = true;
                            continue;
                        }
                        $hasValidData = true;

                        $existingVillage = Village::where('pincode', $code)
                            ->when($officeId !== null && $officeId !== '', fn ($q) => $q->where('office_id', $officeId), fn ($q) => $q->whereNull('office_id'))
                            ->first();

                        // A pincode may contain both completed offices and an
                        // unsynced stub. Preserve completed office data exactly.
                        if ($existingVillage && $this->isVillageSynced($existingVillage)) {
                            continue;
                        }

                        if (! $existingVillage) {
                            // Adopt an un-synced stub record to avoid orphaned duplicates
                            $existingVillage = Village::where('pincode', $code)
                                ->where($this->unsyncedVillageCondition())
                                ->first();
                        }

                        $updateData = [
                            ...$officeData,
                        ];

                        if ($existingVillage) {
                            $updateData['office_id'] = $officeId;
                            $existingVillage->update($updateData);
                        } else {
                            $updateData['office_id'] = $officeId;
                            Village::create($updateData);
                        }

                        $syncedCount++;
                    }
                }

                if (!$hasValidData && $officesList !== []) {
                    Log::warning('India Post returned no usable office records for a village pincode.', [
                        'pincode' => (string) $code,
                        'response_item_count' => count($officesList),
                    ]);
                    $this->markPincodeSyncStatus((string) $code, 'FAILED', $request->user()?->lob_state_name);
                    $errors[] = $code;
                } elseif ($hasInvalidData) {
                    Log::warning('India Post returned partial village office data; unresolved records were marked failed.', [
                        'pincode' => (string) $code,
                    ]);
                    $this->markPincodeSyncStatus((string) $code, 'FAILED', $request->user()?->lob_state_name);
                    $errors[] = $code;
                }

                // Mark pincodes with a genuinely empty response so they are never retried.
                if (! $hasValidData && $officesList === []) {
                    Log::warning('India Post returned no offices for a village pincode.', [
                        'pincode' => (string) $code,
                        'status' => 'FAILED',
                    ]);
                    $this->markPincodeSyncStatus((string) $code, 'FAILED', $request->user()?->lob_state_name);
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync India Post pincode {$code}: " . $e->getMessage());

                $isTransientFailure = $e instanceof \Illuminate\Http\Client\ConnectionException
                    || str_contains(strtolower($e->getMessage()), 'curl')
                    || str_contains(strtolower($e->getMessage()), 'authenticate')
                    || str_contains(strtolower($e->getMessage()), 'unauthorized')
                    || str_contains(strtolower($e->getMessage()), 'timeout');
                $this->markPincodeSyncStatus(
                    (string) $code,
                    $isTransientFailure ? 'API_ERROR' : 'FAILED',
                    $request->user()?->lob_state_name
                );

                $errors[] = $code;

                // Network or Auth errors
                if ($isTransientFailure) {
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
        $lobStateName = $request->user()?->lob_state_name;
        if ($lobStateName) {
            $accessibleIds = Village::withTrashed()
                ->whereIn('id', $ids)
                ->where('state_name', $lobStateName)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            abort_unless(count(array_unique($accessibleIds)) === count(array_unique(array_map('intval', $ids))), 404);
        }

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
            abort_unless($request->user()?->can('village-edit'), 403);
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
                    'priority' => 1,
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

    private function indiaPostOfficesForPincode(string $pincode, IndiaPostProvider $indiaPostProvider): array
    {
        return Cache::remember('india_post_pincode_lookup_'.$pincode, now()->addHour(), function () use ($indiaPostProvider, $pincode): array {
            $response = $indiaPostProvider->getPincodeDetails($pincode);
            $items = $this->indiaPostOfficeList($response);

            return collect($items)
                ->filter(fn ($office) => is_array($office))
                ->map(function (array $office) use ($pincode): array {
                    $normalized = $this->normalizeIndiaPostOffice($office, $pincode);
                    if ($normalized['pincode'] !== $pincode) {
                        Log::warning('India Post returned an office for a different pincode during village creation.', [
                            'requested_pincode' => $pincode,
                            'returned_pincode' => $normalized['pincode'],
                            'office_id' => $normalized['office_id'],
                        ]);
                    }

                    return $normalized;
                })
                ->filter(fn (array $office) => $office['pincode'] === $pincode)
                ->filter(fn (array $office) => trim($office['village_name']) !== '')
                ->values()
                ->all();
        });
    }

    private function indiaPostOfficeList(mixed $response): array
    {
        if (!is_array($response)
            || (array_key_exists('success', $response) && !$this->indiaPostBoolean($response['success']))) {
            throw new \UnexpectedValueException('India Post returned an unsuccessful or invalid response payload.');
        }

        $offices = $response['data'] ?? null;
        if (is_array($offices) && !array_is_list($offices)
            && (isset($offices['office_id']) || isset($offices['office_name']))) {
            $offices = [$offices];
        } elseif ($offices === null && array_is_list($response)) {
            $offices = $response;
        } elseif ($offices === null && (isset($response['office_id']) || isset($response['office_name']))) {
            $offices = [$response];
        }

        if (!is_array($offices) || !array_is_list($offices)) {
            throw new \UnexpectedValueException('India Post response did not contain a valid office list.');
        }

        return $offices;
    }

    /** Map the India Post office payload to the persisted village columns. */
    private function normalizeIndiaPostOffice(array $office, string $fallbackPincode): array
    {
        $villageName = $office['village_name'] ?? null;
        if (!is_string($villageName) || trim($villageName) === '' || $villageName === 'Choose an option') {
            $villageName = $office['office_name'] ?? '';
        }

        $officeId = $office['office_id'] ?? null;

        $pincode = trim((string) ($office['pincode'] ?? $office['pin_code'] ?? ''));

        return [
            'village_name' => is_string($villageName) ? trim($villageName) : '',
            'pincode' => $pincode !== '' ? $pincode : $fallbackPincode,
            'post_so_name' => $office['office_name'] ?? $office['post_so_name'] ?? null,
            'taluka_name' => $office['taluk_name'] ?? $office['taluka_name'] ?? $office['taluk'] ?? null,
            'district_name' => $office['city_name'] ?? $office['district_name'] ?? $office['district'] ?? null,
            'state_name' => $office['state_name'] ?? $office['state'] ?? null,
            'office_id' => $officeId !== null && $officeId !== '' ? (string) $officeId : null,
            'office_type_code' => $office['office_type_code'] ?? null,
            'delivery_office_flag' => $this->indiaPostBoolean($office['delivery_office_flag'] ?? false),
            'is_rolled_out' => $this->indiaPostBoolean($office['is_rolled_out'] ?? false),
        ];
    }

    private function indiaPostBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true);
    }

    private function unsyncedVillageCondition(): \Closure
    {
        return static function ($query): void {
            $query->whereNull('office_type_code')
                ->orWhereIn('office_type_code', ['INVALID', ''])
                ->orWhere(function ($query): void {
                    $query->whereNull('office_id')
                        ->where('office_type_code', '<>', 'FAILED');
                });
        };
    }

    private function isVillageSynced(Village $village): bool
    {
        $officeType = strtoupper(trim((string) $village->office_type_code));

        return filled($village->office_id)
            && $officeType !== ''
            && !in_array($officeType, ['INVALID', 'FAILED', 'API_ERROR'], true);
    }

    private function markPincodeSyncStatus(string $pincode, string $status, ?string $stateName = null): void
    {
        $pendingQuery = Village::where('pincode', $pincode)
            ->where($this->unsyncedVillageCondition());
        $hasPendingRecords = $pendingQuery->exists();
        if ($hasPendingRecords) {
            $pendingQuery->update(['office_type_code' => $status]);
        }

        // Persist the failure as a searchable management row when there was no
        // unresolved row to annotate. This preserves all completed office rows.
        $hasStatusRow = Village::where('pincode', $pincode)
            ->where('village_name', 'India Post sync failure')
            ->where('office_type_code', $status)
            ->exists();
        if (!$hasPendingRecords && !$hasStatusRow && strlen($pincode) <= 10) {
            Village::create([
                'village_name' => 'India Post sync failure',
                'pincode' => $pincode,
                'state_name' => $stateName,
                'office_type_code' => $status,
            ]);
        }
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
            ->where('village_name', '<>', 'India Post sync failure')
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
            if ((string) $request->input('service_id') === 'unassigned') {
                $query->whereDoesntHave('mappings', fn ($q) => $q->where('is_available', true));
            } else {
                $serviceId = (int) $request->input('service_id');
                $query->whereHas('mappings', function ($q) use ($serviceId): void {
                    $q->where('service_id', $serviceId)->where('is_available', true);
                });
            }
        }

        if ($request->filled('office_type_code')) {
            $officeTypes = array_filter(array_map('trim', explode(',', (string) $request->input('office_type_code'))));
            if (! empty($officeTypes)) {
                $query->whereIn('office_type_code', $officeTypes);
            }
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

        // cursor() hydrates one model at a time and does not eager-load relations,
        // so the CSV callback would issue relation queries for every village.
        // lazyById() fetches bounded chunks and eager-loads mappings/services per chunk.
        $villages = $query->with(['mappings.service'])->lazyById(500);
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

        // Keep selected exports bounded while eager-loading their related services.
        $villages = Village::withTrashed()->with(['mappings.service'])
            ->whereIn('id', $validated['ids'])
            ->when($request->user()?->lob_state_name, fn ($q, $state) => $q->where('state_name', $state))
            ->lazyById(500);
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

    private function assertVillageStateAccess(Village $village, Request $request): void
    {
        $lobStateName = $request->user()?->lob_state_name;
        abort_if($lobStateName && $village->state_name !== $lobStateName, 404);
    }
}
