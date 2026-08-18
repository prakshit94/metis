<?php

declare(strict_types=1);

namespace App\Modules\Customers\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Models\Crop;
use App\Models\IrrigationType;
use App\Models\LandUnit;
use App\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerSettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings-view', only: ['index', 'list']),
            new Middleware('permission:settings-edit', only: ['store', 'update', 'toggle', 'destroy']),
        ];
    }
    protected array $models = [
        'crop' => Crop::class,
        'irrigation' => IrrigationType::class,
        'land_unit' => LandUnit::class,
        'lead_source' => LeadSource::class,
    ];

    public function index()
    {
        return view('customers.settings');
    }

    public function list(string $type)
    {
        if (!array_key_exists($type, $this->models)) {
            return response()->json(['error' => 'Invalid settings type.'], 400);
        }

        $modelClass = $this->models[$type];
        $items = $modelClass::orderBy('name')->get();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request, string $type)
    {
        if (!array_key_exists($type, $this->models)) {
            return response()->json(['error' => 'Invalid settings type.'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $modelClass = $this->models[$type];
        $item = $modelClass::create([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $this->clearCache($type);

        return response()->json([
            'success' => true, 
            'message' => ucfirst(str_replace('_', ' ', $type)) . ' created successfully.',
            'item' => $item
        ]);
    }

    public function update(Request $request, string $type, $id)
    {
        if (!array_key_exists($type, $this->models)) {
            return response()->json(['error' => 'Invalid settings type.'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $modelClass = $this->models[$type];
        $model = $modelClass::findOrFail($id);
        $model->update([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? $model->is_active,
        ]);

        $this->clearCache($type);

        return response()->json([
            'success' => true, 
            'message' => ucfirst(str_replace('_', ' ', $type)) . ' updated successfully.',
            'item' => $model
        ]);
    }

    public function toggle(Request $request, string $type, $id)
    {
        if (!array_key_exists($type, $this->models)) {
            return response()->json(['error' => 'Invalid settings type.'], 400);
        }

        $modelClass = $this->models[$type];
        $model = $modelClass::findOrFail($id);
        $model->update([
            'is_active' => !$model->is_active,
        ]);

        $this->clearCache($type);

        return response()->json([
            'success' => true, 
            'message' => ucfirst(str_replace('_', ' ', $type)) . ' status toggled.',
            'is_active' => $model->is_active
        ]);
    }
    
    public function destroy(string $type, $id)
    {
        if (!array_key_exists($type, $this->models)) {
            return response()->json(['error' => 'Invalid settings type.'], 400);
        }

        $modelClass = $this->models[$type];
        $model = $modelClass::findOrFail($id);
        $model->delete();

        $this->clearCache($type);

        return response()->json([
            'success' => true, 
            'message' => ucfirst(str_replace('_', ' ', $type)) . ' deleted successfully.'
        ]);
    }

    protected function clearCache(string $type)
    {
        $cacheKey = match ($type) {
            'crop' => 'dynamic_crops_obj',
            'irrigation' => 'dynamic_irrigation_types_obj',
            'land_unit' => 'dynamic_land_units_obj',
            'lead_source' => 'dynamic_lead_sources_obj',
            default => null,
        };

        if ($cacheKey) {
            Cache::forget($cacheKey);
        }
        
        // Also clear the modal specific caches
        $modalCacheKey = match ($type) {
            'crop' => 'dynamic_crops',
            'irrigation' => 'dynamic_irrigation_types',
            'land_unit' => 'dynamic_land_units',
            'lead_source' => 'dynamic_lead_sources',
            default => null,
        };
        
        if ($modalCacheKey) {
            Cache::forget($modalCacheKey);
        }
    }
}
