<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Designation;
use App\Modules\Users\Models\EmploymentType;
use Illuminate\Http\Request;

class HrSettingController extends Controller
{
    private function getModel(string $type)
    {
        return match ($type) {
            'designations' => Designation::class,
            'employment_types' => EmploymentType::class,
            default => null,
        };
    }

    public function list(string $type)
    {
        $model = $this->getModel($type);
        if (! $model) {
            return response()->json(['error' => 'Invalid setting type.'], 400);
        }

        $items = $model::orderBy('id')->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, string $type)
    {
        $model = $this->getModel($type);
        if (! $model) {
            return response()->json(['error' => 'Invalid setting type.'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:'.(new $model)->getTable().',name',
            'is_active' => 'boolean',
        ]);

        $item = $model::create([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['success' => true, 'message' => 'Created successfully.', 'data' => $item]);
    }

    public function update(Request $request, string $type, string $id)
    {
        $model = $this->getModel($type);
        if (! $model) {
            return response()->json(['error' => 'Invalid setting type.'], 400);
        }

        $item = $model::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:'.(new $model)->getTable().',name,'.$id,
            'is_active' => 'boolean',
        ]);

        $item->update([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? $item->is_active,
        ]);

        return response()->json(['success' => true, 'message' => 'Updated successfully.', 'data' => $item]);
    }

    public function destroy(string $type, string $id)
    {
        $model = $this->getModel($type);
        if (! $model) {
            return response()->json(['error' => 'Invalid setting type.'], 400);
        }

        $item = $model::findOrFail($id);
        $item->delete();

        return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
    }

    public function toggleActive(string $type, string $id)
    {
        $model = $this->getModel($type);
        if (! $model) {
            return response()->json(['error' => 'Invalid setting type.'], 400);
        }

        $item = $model::findOrFail($id);
        $item->is_active = ! $item->is_active;
        $item->save();

        return response()->json(['success' => true, 'message' => 'Status toggled.', 'is_active' => $item->is_active]);
    }
}
