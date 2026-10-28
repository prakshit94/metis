<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Core\Models\Village;
use App\Modules\Users\Models\Team;
use App\Services\OrganizationAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:team-view', only: ['index', 'show']),
            new Middleware('permission:team-create', only: ['store']),
            new Middleware('permission:team-edit', only: ['update']),
            new Middleware('permission:team-delete', only: ['destroy'])];
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate(['search' => 'sometimes|nullable|string|max:150', 'is_active' => 'sometimes|in:0,1,true,false',
            'per_page' => 'sometimes|integer|min:1|max:1000', 'page' => 'sometimes|integer|min:1']);
        $scope = $request->user()?->lob_team_id;
        $query = Team::query()->withCount(['offices', 'members' => fn ($q) => $q->select(DB::raw('COUNT(DISTINCT users.id)'))])
            ->when($scope !== null, fn ($q) => $q->whereKey($scope))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($search) => $search
                ->where('name', 'like', '%'.$request->input('search').'%')->orWhere('code', 'like', '%'.$request->input('search').'%')))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')->orderBy('id');
        $perPage = (int)$request->input('per_page', 15);
        $page = $query->paginate($perPage);
        if ($page->isEmpty() && $page->currentPage() > $page->lastPage()) {
            $page = $query->paginate($perPage, ['*'], 'page', $page->lastPage());
        }
        return response()->json(['data' => $page->items(), 'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total(),
            'from' => $page->firstItem() ?? 0, 'to' => $page->lastItem() ?? 0,
            'states' => $this->states(), 'state_codes' => Team::STATE_NAME_MAP]);
    }

    private function states(): array
    {
        return Cache::remember('organization_states', 3600, fn () => Village::query()->distinct()->pluck('state_name')
            ->merge(array_values(Team::STATE_NAME_MAP))->filter()->unique()->sort()->values()->all());
    }

    private function validated(Request $request, ?Team $team = null): array
    {
        if ($request->has('code')) $request->merge(['code' => strtoupper(trim((string)$request->input('code')))]);
        $data = $request->validate(['name' => [$team ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('teams')->ignore($team?->id)],
            'code' => [$team ? 'sometimes' : 'required', 'string', 'max:50', 'regex:/^[A-Z][A-Z0-9]{0,49}$/', Rule::unique('teams')->ignore($team?->id)],
            'state_name' => ['sometimes', 'nullable', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean']]);
        $code = $data['code'] ?? $team?->code;
        $state = array_key_exists('state_name', $data) ? $data['state_name'] : $team?->state_name;
        if (isset(Team::STATE_NAME_MAP[$code])) {
            $canonical = Team::STATE_NAME_MAP[$code];
            if ($request->filled('state_name') && $state !== $canonical) {
                throw ValidationException::withMessages(['state_name' => 'This state code maps to '.$canonical.'.']);
            }
            $state = $canonical;
        }
        if (!$state || !in_array($state, $this->states(), true)) {
            throw ValidationException::withMessages(['state_name' => 'Select a valid state. State teams cannot have global or missing mappings.']);
        }
        $data['state_name'] = $state;
        return $data;
    }

    public function store(Request $request): JsonResponse
    {
        abort_if($request->user()?->lob_team_id !== null, 403, 'Only global team administrators can add states.');
        return DB::transaction(function () use ($request) {
            $team = Team::create($this->validated($request));
            app(OrganizationAccess::class)->audit($request, 'state', $team->id, 'created', null, $team->getAttributes());
            return response()->json(['message' => 'State team created successfully.', 'data' => $team], 201);
        }, 3);
    }

    public function show(Request $request, Team $team): JsonResponse
    {
        $access = app(OrganizationAccess::class);
        $access->assertTeam($request, $team->id);
        return response()->json(['data' => $team, 'dependencies' => $access->dependencies($team)]);
    }

    public function update(Request $request, Team $team): JsonResponse
    {
        $access = app(OrganizationAccess::class);
        $access->assertTeam($request, $team->id);
        return DB::transaction(function () use ($request, $team, $access) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            $before = $team->getAttributes();
            $data = $this->validated($request, $team);
            if (($data['code'] ?? $team->code) !== $team->code || $data['state_name'] !== $team->resolved_state_name) {
                if (array_sum($access->dependencies($team)) > 0) {
                    throw ValidationException::withMessages(['code' => 'State code and mapping cannot change while users, offices, permissions, targets or orders depend on them.']);
                }
            }
            $team->update($data);
            $access->audit($request, 'state', $team->id, 'updated', $before, $team->getAttributes());
            return response()->json(['message' => 'State team updated successfully.', 'data' => $team]);
        }, 3);
    }

    public function destroy(Request $request, Team $team): JsonResponse
    {
        $access = app(OrganizationAccess::class);
        $access->assertTeam($request, $team->id);
        return DB::transaction(function () use ($request, $team, $access) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            abort_if(array_sum($access->dependencies($team)) > 0, 409, 'This state has dependent users, offices, permissions, targets or orders. Reassign dependencies or mark it inactive instead.');
            $before = $team->getAttributes();
            $team->delete();
            $access->audit($request, 'state', $team->id, 'deleted', $before, null);
            return response()->json(['message' => 'Unused state team deleted successfully.']);
        }, 3);
    }
}
