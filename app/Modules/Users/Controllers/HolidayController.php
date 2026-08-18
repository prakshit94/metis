<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class HolidayController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:leave-view', only: ['index', 'show']),
            new Middleware('permission:leave-create', only: ['store']),
            new Middleware('permission:leave-edit', only: ['update']),
            new Middleware('permission:leave-delete', only: ['destroy', 'bulkAction']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $holidays = Holiday::orderBy('date', 'asc')->paginate((int) $request->input('per_page', 15));
        return response()->json($holidays);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|string',
        ]);

        $holiday = Holiday::create($validated);

        return response()->json(['data' => $holiday, 'message' => 'Holiday created successfully.']);
    }
    
    public function update(Request $request, Holiday $holiday): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|string',
        ]);

        $holiday->update($validated);

        return response()->json(['data' => $holiday, 'message' => 'Holiday updated successfully.']);
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        $holiday->delete();
        return response()->json(['message' => 'Holiday deleted successfully.']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:holidays,id',
        ]);

        if ($validated['action'] === 'delete') {
            Holiday::whereIn('id', $validated['ids'])->delete();
            return response()->json(['message' => 'Selected holidays deleted successfully.']);
        }
        
        return response()->json(['message' => 'Invalid action.'], 400);
    }
}
