<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentTrackingEvent;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    /**
     * Get paginated shipments list with filters.
     */
    public function shipmentsIndex(Request $request): JsonResponse
    {
        $this->authorize('shipping.view');

        $query = Shipment::with('order');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shipment_no', 'like', "%{$search}%")
                  ->orWhere('tracking_no', 'like', "%{$search}%")
                  ->orWhere('carrier_name', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('order_no', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->query('status')) {
            if ($status === 'in_transit') {
                $query->whereIn('status', ['in_transit', 'shipped']);
            } else {
                $query->where('status', $status);
            }
        }

        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'shipment_no', 'status', 'shipped_at', 'delivered_at'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = min(max($perPage, 1), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Update shipment status and add a tracking event automatically.
     */
    public function updateShipmentStatus(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('shipping.manage');

        $validated = $request->validate([
            'status' => 'required|in:pending,shipped,in_transit,delivered,failed,returned',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($shipment, $validated) {
            $oldStatus = $shipment->status;
            $newStatus = $validated['status'] === 'shipped' ? 'in_transit' : $validated['status'];

            $updateData = ['status' => $newStatus];

            if ($newStatus === 'in_transit' && !$shipment->shipped_at) {
                $updateData['shipped_at'] = now();
            }

            if ($newStatus === 'delivered' && !$shipment->delivered_at) {
                $updateData['delivered_at'] = now();
            }

            $shipment->update($updateData);

            // Create tracking event
            ShipmentTrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_name' => 'Status Updated to ' . ucfirst($newStatus),
                'location' => $validated['location'] ?? 'Hub Location',
                'description' => $validated['description'] ?? "Shipment status changed from {$oldStatus} to {$newStatus}.",
                'occurred_at' => now(),
            ]);

            // Sync with Order Status if needed
            $order = $shipment->order;
            if ($order) {
                if ($newStatus === 'in_transit') {
                    $order->update(['status' => 'dispatched']);
                } elseif ($newStatus === 'delivered') {
                    $order->update(['status' => 'delivered']);
                } elseif ($newStatus === 'returned') {
                    if ($order->status !== 'returned') {
                        $inventoryService = app(\App\Services\InventoryService::class);
                        $inventoryService->returnOrder($order);
                    }
                }
            }
        });

        return response()->json([
            'message' => 'Shipment status updated successfully.',
            'shipment' => $shipment->load('order'),
        ]);
    }

    /**
     * Get shipment tracking details and history.
     */
    public function trackingEvents(Shipment $shipment): JsonResponse
    {
        $this->authorize('shipping.view');

        return response()->json([
            'shipment' => $shipment,
            'events' => $shipment->events()->orderBy('occurred_at', 'desc')->get(),
        ]);
    }

    /**
     * Add manual tracking event to shipment.
     */
    public function addTrackingEvent(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('shipping.manage');

        $validated = $request->validate([
            'event_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $event = null;
        DB::transaction(function () use ($shipment, $validated, &$event) {
            $shipment = Shipment::lockForUpdate()->findOrFail($shipment->id);

            $event = ShipmentTrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_name' => $validated['event_name'],
                'location' => $validated['location'],
                'description' => $validated['description'],
                'occurred_at' => now(),
            ]);

            // Enterprise flow:
            // in_transit = carrier has taken custody and the parcel is moving
            if ($shipment->status === 'shipped') {
                $shipment->update(['status' => 'in_transit']);
            }
        });

        return response()->json([
            'message' => 'Tracking event added successfully.',
            'event' => $event,
        ], 201);
    }

    /**
     * Get all delivery services.
     */
    public function servicesIndex(Request $request): JsonResponse
    {
        $this->authorize('shipping.view');

        $query = Service::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'name', 'code', 'is_active'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = min(max($perPage, 1), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Create a new shipping service.
     */
    public function storeService(Request $request): JsonResponse
    {
        $this->authorize('shipping.manage');

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:services,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $service = Service::create($validated);

        return response()->json([
            'message' => 'Shipping Service created successfully.',
            'service' => $service,
        ], 201);
    }

    /**
     * Update an existing shipping service.
     */
    public function updateService(Request $request, Service $service): JsonResponse
    {
        $this->authorize('shipping.manage');

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:services,code,' . $service->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $service->update($validated);

        return response()->json([
            'message' => 'Shipping Service updated successfully.',
            'service' => $service,
        ]);
    }

    /**
     * Toggle service active status.
     */
    public function toggleService(Service $service): JsonResponse
    {
        $this->authorize('shipping.manage');

        $service->update(['is_active' => !$service->is_active]);

        return response()->json([
            'message' => 'Shipping Service status toggled successfully.',
            'service' => $service,
        ]);
    }

    /**
     * Delete a shipping service.
     */
    public function destroyService(Service $service): JsonResponse
    {
        $this->authorize('shipping.manage');

        $service->delete();

        return response()->json([
            'message' => 'Shipping Service deleted successfully.'
        ]);
    }
}
