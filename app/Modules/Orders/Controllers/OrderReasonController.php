<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\CancelReason;
use App\Modules\Orders\Models\DeliveryFailureReason;
use App\Modules\Orders\Models\RescheduleReason;
use App\Modules\Orders\Models\ReturnReason;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class OrderReasonController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orderreason-view', only: ['index', 'list']),
            new Middleware('permission:orderreason-create', only: ['store']),
            new Middleware('permission:orderreason-edit', only: ['update', 'toggleActive']),
            new Middleware('permission:orderreason-delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        return view('order-reasons.index');
    }

    private function getModel(string $type)
    {
        return match ($type) {
            'reschedule' => RescheduleReason::class,
            'return' => ReturnReason::class,
            'failure' => DeliveryFailureReason::class,
            'cancel' => CancelReason::class,
            default => null,
        };
    }

    public function list(string $type)
    {
        $model = $this->getModel($type);
        if (! $model) {
            return response()->json(['error' => 'Invalid reason type.'], 400);
        }

        $reasons = $model::with(['creator', 'updater'])->orderBy('id')->get();

        return response()->json(['reasons' => $reasons]);
    }

    public function store(Request $request, string $type)
    {
        $model = $this->getModel($type);
        if (! $model) {
            return response()->json(['error' => 'Invalid reason type.'], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255|unique:'.(new $model)->getTable().',reason',
            'is_active' => 'boolean',
        ]);

        $reason = $model::create([
            'reason' => $validated['reason'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['success' => true, 'message' => 'Reason created successfully.', 'reason' => $reason]);
    }

    public function update(Request $request, string $type, string $id)
    {
        $model = $this->getModel($type);
        if (! $model) {
            return response()->json(['error' => 'Invalid reason type.'], 400);
        }

        $reason = $model::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:255|unique:'.(new $model)->getTable().',reason,'.$id,
            'is_active' => 'boolean',
        ]);

        $reason->update([
            'reason' => $validated['reason'],
            'is_active' => $validated['is_active'] ?? $reason->is_active,
        ]);

        return response()->json(['success' => true, 'message' => 'Reason updated successfully.', 'reason' => $reason]);
    }

    public function destroy(string $type, string $id)
    {
        $model = $this->getModel($type);
        if (! $model) {
            return response()->json(['error' => 'Invalid reason type.'], 400);
        }

        $reason = $model::findOrFail($id);
        $reason->delete();

        return response()->json(['success' => true, 'message' => 'Reason deleted successfully.']);
    }

    public function toggleActive(string $type, string $id)
    {
        $model = $this->getModel($type);
        if (! $model) {
            return response()->json(['error' => 'Invalid reason type.'], 400);
        }

        $reason = $model::findOrFail($id);
        $reason->is_active = ! $reason->is_active;
        $reason->save();

        return response()->json(['success' => true, 'message' => 'Reason status toggled.', 'is_active' => $reason->is_active]);
    }
}
