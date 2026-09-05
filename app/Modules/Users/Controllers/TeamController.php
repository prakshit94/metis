<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use App\Modules\Core\Models\Village;

class TeamController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:team-view', only: ['index', 'show']),
            new Middleware('permission:team-create', only: ['store']),
            new Middleware('permission:team-edit', only: ['update']),
            new Middleware('permission:team-delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        
        $teams = Team::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->input('search') . '%';
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            })
            ->when($request->has('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name', 'asc');

        if ($perPage === 1000) {
            $data = $teams->get();
            return response()->json([
                'data' => $data,
                'states' => $this->getStatesList()
            ]);
        }

        $paginated = $teams->paginate($perPage);
        return response()->json([
            'data' => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'states' => $this->getStatesList()
        ]);
    }

    private function getStatesList()
    {
        return Cache::remember('geo_states', 3600, function () {
            return Village::distinct()->pluck('state_name')->filter()->sort()->values();
        });
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:teams,name'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50', 'unique:teams,code'],
            'state_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $team = Team::create($validated);

        return response()->json([
            'message' => "Team [{$team->name}] created successfully.",
            'data' => $team,
        ], 201);
    }

    public function show(Team $team): JsonResponse
    {
        return response()->json(['data' => $team]);
    }

    public function update(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:teams,name,' . $team->id],
            'code' => ['sometimes', 'nullable', 'string', 'max:50', 'unique:teams,code,' . $team->id],
            'state_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $team->update($validated);

        return response()->json([
            'message' => "Team [{$team->name}] updated successfully.",
            'data' => $team,
        ]);
    }

    public function destroy(Team $team): JsonResponse
    {
        $team->delete();
        
        return response()->json([
            'message' => "Team [{$team->name}] deleted successfully."
        ]);
    }
}
