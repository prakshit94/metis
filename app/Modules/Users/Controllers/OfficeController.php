<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Office;
use App\Modules\Users\Models\Team;
use App\Services\OrganizationAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfficeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:office-view|user-sync-roles', only: ['index']),
            new Middleware('permission:office-view', only: ['show']),
            new Middleware('permission:office-create', only: ['store']),
            new Middleware('permission:office-edit', only: ['update']),
            new Middleware('permission:office-delete', only: ['destroy'])];
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate(['team_id' => 'sometimes|nullable|integer|exists:teams,id', 'search' => 'sometimes|nullable|string|max:150',
            'is_active' => 'sometimes|in:0,1,true,false', 'page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:1000']);
        $scope = $request->user()?->lob_team_id;
        $query = Office::with('team:id,name,code,state_name,is_active')->withCount('members')
            ->when($scope !== null, fn ($q) => $q->where('team_id', $scope))
            ->when($request->filled('team_id'), fn ($q) => $q->where('team_id', $request->integer('team_id')))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', '%'.$request->input('search').'%')->orWhere('code', 'like', '%'.$request->input('search').'%')))
            ->orderBy('name')->orderBy('id');
        $page = $query->paginate($request->integer('per_page', 100));
        return response()->json($page)->header('Cache-Control', 'no-store');
    }

    private function validated(Request $request, ?Office $office = null): array
    {
        if ($request->has('code')) $request->merge(['code' => strtoupper(trim((string)$request->input('code')))]);
        $teamId = $request->input('team_id', $office?->team_id);
        $data = $request->validate(['team_id' => [$office ? 'sometimes' : 'required', 'integer', 'exists:teams,id'],
            'name' => [$office ? 'sometimes' : 'required', 'string', 'max:150', Rule::unique('offices')->where('team_id', $teamId)->ignore($office?->id)],
            'code' => [$office ? 'sometimes' : 'required', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9_-]{0,29}$/', Rule::unique('offices')->where('team_id', $teamId)->ignore($office?->id)],
            'address_line_1' => 'sometimes|nullable|string|max:255', 'address_line_2' => 'sometimes|nullable|string|max:255',
            'village_id' => 'sometimes|nullable|integer|exists:villages,id', 'village_name' => 'sometimes|nullable|string|max:255',
            'post_office' => 'sometimes|nullable|string|max:255', 'taluka' => 'sometimes|nullable|string|max:255',
            'district' => 'sometimes|nullable|string|max:255', 'state' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|nullable|string|max:255', 'city' => 'sometimes|nullable|string|max:100',
            'pincode' => 'sometimes|nullable|string|max:20', 'description' => 'sometimes|nullable|string|max:5000', 'is_active' => 'sometimes|boolean']);
        // Keep legacy office clients compatible while retaining structured address details.
        if (array_key_exists('address_line_1', $data)) $data['address'] = $data['address_line_1'];
        elseif (array_key_exists('address', $data)) $data['address_line_1'] = $data['address'];
        app(OrganizationAccess::class)->assertTeam($request, (int)$teamId);
        $team = Team::whereKey($teamId)->lockForUpdate()->firstOrFail();
        if (!$office && (!$team->is_active || !$team->resolved_state_name)) {
            throw ValidationException::withMessages(['team_id' => 'Create offices under an active, mapped state.']);
        }
        if ($office && (int)$teamId !== $office->team_id) {
            throw ValidationException::withMessages(['team_id' => 'An office cannot be moved between states. Create another office and explicitly reassign agents.']);
        }
        return $data;
    }

    public function store(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $office = Office::create($this->validated($request));
            app(OrganizationAccess::class)->audit($request, 'office', $office->id, 'created', null, $office->getAttributes());
            return response()->json(['message' => 'Office created successfully.', 'data' => $office], 201);
        }, 3);
    }

    public function show(Request $request, Office $office): JsonResponse
    {
        app(OrganizationAccess::class)->assertTeam($request, $office->team_id);
        return response()->json(['data' => $office->load('team')->loadCount('members')]);
    }

    public function update(Request $request, Office $office): JsonResponse
    {
        $access = app(OrganizationAccess::class);
        $access->assertTeam($request, $office->team_id);
        return DB::transaction(function () use ($request, $office, $access) {
            // State lock first, matching office creation/deletion and assignment validation.
            Team::whereKey($office->team_id)->lockForUpdate()->firstOrFail();
            $office = Office::whereKey($office->id)->lockForUpdate()->firstOrFail();
            $before = $office->getAttributes();
            $office->update($this->validated($request, $office));
            $access->audit($request, 'office', $office->id, 'updated', $before, $office->getAttributes());
            return response()->json(['message' => 'Office updated successfully.', 'data' => $office]);
        }, 3);
    }

    public function destroy(Request $request, Office $office): JsonResponse
    {
        $access = app(OrganizationAccess::class);
        $access->assertTeam($request, $office->team_id);
        return DB::transaction(function () use ($request, $office, $access) {
            Team::whereKey($office->team_id)->lockForUpdate()->firstOrFail();
            $office = Office::whereKey($office->id)->lockForUpdate()->firstOrFail();
            abort_if($office->members()->withTrashed()->exists() || $office->orders()->withTrashed()->exists(), 409,
                'This office has users or historical orders. Reassign users or mark the office inactive; order history is retained.');
            $before = $office->getAttributes();
            $office->delete();
            $access->audit($request, 'office', $office->id, 'deleted', $before, null);
            return response()->json(['message' => 'Unused office deleted successfully.']);
        }, 3);
    }
}
