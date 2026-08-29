<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $departments = Department::with('manager')->paginate(request('per_page', 15));

        return response()->json($departments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:departments,name',
            'code' => 'nullable|string|unique:departments,code',
            'manager_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:departments,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $department = Department::create($validated);

        return response()->json(['data' => $department, 'message' => 'Department created successfully.'], 201);
    }

    public function show(Department $department): JsonResponse
    {
        $department->load('manager', 'users');

        return response()->json(['data' => $department]);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|unique:departments,name,'.$department->id,
            'code' => 'nullable|string|unique:departments,code,'.$department->id,
            'manager_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:departments,id|not_in:'.$department->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return response()->json(['data' => $department, 'message' => 'Department updated successfully.']);
    }

    public function destroy(Department $department): Response
    {
        $department->delete();

        return response()->noContent();
    }
}
