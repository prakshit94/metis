<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Core\Models\Village;
use App\Modules\Catalog\Models\Service;
use App\Modules\Core\Models\VillageServiceMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class VillageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:village-view', only: ['index', 'show', 'servicesOptions', 'search']),
            new Middleware('permission:village-create', only: ['store', 'import']),
            new Middleware('permission:village-edit', only: ['update', 'bulkAction']),
            new Middleware('permission:village-delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of villages with pagination, search, sorting and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $sortMap = [
            'id'            => 'id',
            'village_name'  => 'village_name',
            'pincode'       => 'pincode',
            'taluka_name'   => 'taluka_name',
            'district_name' => 'district_name',
            'state_name'    => 'state_name',
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
            if (!empty($states)) {
                $query->whereIn('state_name', $states);
            }
        }

        if ($request->filled('district')) {
            $districts = array_filter(array_map('trim', explode(',', (string) $request->input('district'))));
            if (!empty($districts)) {
                $query->whereIn('district_name', $districts);
            }
        }

        if ($request->filled('taluka')) {
            $talukas = array_filter(array_map('trim', explode(',', (string) $request->input('taluka'))));
            if (!empty($talukas)) {
                $query->whereIn('taluka_name', $talukas);
            }
        }

        if ($request->filled('service_id')) {
            $serviceId = (int) $request->input('service_id');
            $query->whereHas('mappings', function ($q) use ($serviceId): void {
                $q->where('service_id', $serviceId)->where('is_available', true);
            });
        }

        // Stats calculation
        $statsQuery = clone $query;
        $counts = $statsQuery->select([
            DB::raw("COUNT(*) as total"),
            DB::raw("COUNT(DISTINCT pincode) as pincodes"),
            DB::raw("COUNT(DISTINCT district_name) as districts_count"),
        ])->toBase()->first();

        $stats = [
            'total'           => (int) ($counts->total ?? 0),
            'pincodes'        => (int) ($counts->pincodes ?? 0),
            'districts_count' => (int) ($counts->districts_count ?? 0),
            'services'        => Service::active()->count(),
        ];

        $villages = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        // Include filters lists
        $statesList = DB::table('villages')->distinct()->pluck('state_name')->filter()->sort()->values();
        $districtsList = DB::table('villages')->distinct()->pluck('district_name')->filter()->sort()->values();

        return response()->json([
            'pagination' => $villages,
            'stats'      => $stats,
            'states'     => $statesList,
            'districts'  => $districtsList,
        ]);
    }

    /**
     * Store a new village.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'village_name'  => ['required', 'string', 'max:255'],
            'pincode'       => ['required', 'string', 'max:10'],
            'post_so_name'  => ['nullable', 'string', 'max:255'],
            'taluka_name'   => ['nullable', 'string', 'max:255'],
            'district_name' => ['nullable', 'string', 'max:255'],
            'state_name'    => ['nullable', 'string', 'max:255'],
        ]);

        $village = Village::create($validated);

        return response()->json([
            'message' => "Village [{$village->village_name}] created successfully.",
            'data'    => $village,
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
            'village_name'  => ['required', 'string', 'max:255'],
            'pincode'       => ['required', 'string', 'max:10'],
            'post_so_name'  => ['nullable', 'string', 'max:255'],
            'taluka_name'   => ['nullable', 'string', 'max:255'],
            'district_name' => ['nullable', 'string', 'max:255'],
            'state_name'    => ['nullable', 'string', 'max:255'],
        ]);

        $village->update($validated);

        if ($request->has('services')) {
            $services = $request->input('services'); // e.g. [service_id => [is_available => 1, priority => 10, remarks => '...']]
            foreach ($services as $serviceId => $data) {
                if (!empty($data['is_available'])) {
                    VillageServiceMapping::updateOrCreate(
                        ['village_id' => $village->id, 'service_id' => (int) $serviceId],
                        [
                            'is_available'          => true,
                            'priority'              => (int) ($data['priority'] ?? 0),
                            'remarks'               => $data['remarks'] ?? null,
                            'serviceable_from_date' => $data['serviceable_from_date'] ?? null,
                            'serviceable_to_date'   => $data['serviceable_to_date'] ?? null,
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
            'data'    => $village->load(['services', 'mappings.service']),
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
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action'     => ['required', 'string', 'in:delete,service-update'],
            'ids'        => ['required', 'array', 'min:1'],
            'ids.*'      => ['integer', 'exists:villages,id'],
            'service_id' => ['required_if:action,service-update', 'nullable', 'integer', 'exists:services,id'],
            'status'     => ['required_if:action,service-update', 'nullable', 'string', 'in:available,unavailable'],
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];

        if ($action === 'delete') {
            Village::whereIn('id', $ids)->delete();
            return response()->json([
                'message' => count($ids) . ' village(s) deleted successfully.',
                'deleted' => $ids,
            ]);
        }

        if ($action === 'service-update') {
            $serviceId = (int) $validated['service_id'];
            $isAvailable = $validated['status'] === 'available';

            $mappings = [];
            foreach ($ids as $id) {
                $mappings[] = [
                    'village_id'            => $id,
                    'service_id'            => $serviceId,
                    'is_available'          => $isAvailable,
                    'serviceable_from_date' => null,
                    'serviceable_to_date'   => null,
                    'remarks'               => 'Bulk updated via admin',
                    'priority'              => 0,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ];
            }

            // Using upsert
            DB::table('village_service_mappings')->upsert(
                $mappings,
                ['village_id', 'service_id'],
                ['is_available', 'remarks', 'updated_at']
            );

            return response()->json([
                'message' => 'Service status updated successfully for ' . count($ids) . ' village(s).',
                'ids'     => $ids,
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
        if (!$request->filled('q') || strlen((string) $request->input('q')) < 3) {
            return response()->json(['data' => []]);
        }

        $term = (string) $request->input('q');
        $villages = Village::search($term)
            ->limit(30)
            ->get();

        return response()->json(['data' => $villages]);
    }

    /**
     * Import villages via CSV file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

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
                        'village_name'    => $row[0],
                        'normalized_name' => strtolower(trim($row[0])),
                        'pincode'         => $row[1],
                        'post_so_name'    => ($row[2] ?? null) === '#N/A' ? null : ($row[2] ?? null),
                        'taluka_name'     => ($row[3] ?? null) === '#N/A' ? null : ($row[3] ?? null),
                        'district_name'   => ($row[4] ?? null) === '#N/A' ? null : ($row[4] ?? null),
                        'state_name'      => ($row[5] ?? null) === '#N/A' ? null : ($row[5] ?? null),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                })->filter()->toArray();

                DB::table('villages')->insert($data);
            });
        });

        return response()->json([
            'message' => 'Villages imported successfully.',
        ]);
    }
}
