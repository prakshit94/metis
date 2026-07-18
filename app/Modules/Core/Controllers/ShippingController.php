<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Services\InventoryService;


use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Shipment;
use App\Modules\Orders\Models\ShipmentTrackingEvent;
use App\Modules\Catalog\Models\Service;
use App\Modules\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ShippingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:shipping-view', only: ['shipmentsIndex', 'trackingEvents', 'servicesIndex']),
            new Middleware('permission:shipping-manage', only: ['addTrackingEvent', 'storeService', 'updateShipmentStatus', 'updateService', 'toggleService', 'destroyService', 'shipmentsBulk', 'servicesBulk', 'providerOptions']),
        ];
    }

    /**
     * Get paginated shipments list with filters.
     */
    public function shipmentsIndex(Request $request): JsonResponse
    {

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

        $validated = $request->validate([
            'status' => 'required|in:pending,shipped,in_transit,delivered,failed,returned',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'delivery_attempts' => 'nullable|integer|min:0',
            'next_followup_date' => 'nullable|date',
            'delivered_by' => 'nullable|string|max:255',
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
            
            if (isset($validated['delivery_attempts'])) {
                $updateData['delivery_attempts'] = $validated['delivery_attempts'];
            }
            if (isset($validated['next_followup_date'])) {
                $updateData['next_followup_date'] = $validated['next_followup_date'];
            }
            if (isset($validated['delivered_by'])) {
                $updateData['delivered_by'] = $validated['delivered_by'];
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
     * Handle bulk actions for shipments.
     */
    public function shipmentsBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:mark_in_transit,mark_delivered,mark_returned',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:shipments,id',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];

        DB::transaction(function () use ($action, $ids) {
            $shipments = Shipment::whereIn('id', $ids)->get();

            foreach ($shipments as $shipment) {
                $statusMap = [
                    'mark_in_transit' => 'in_transit',
                    'mark_delivered' => 'delivered',
                    'mark_returned' => 'returned',
                ];

                $newStatus = $statusMap[$action] ?? $shipment->status;
                
                if ($shipment->status === $newStatus) continue;

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
                    'event_name' => 'Status Updated to ' . ucfirst($newStatus) . ' (Bulk)',
                    'location' => 'Bulk Update',
                    'description' => "Shipment status changed to {$newStatus} via bulk action.",
                    'occurred_at' => now(),
                ]);
            }
        });

        return response()->json([
            'message' => 'Bulk action completed successfully.',
        ]);
    }

    /**
     * Get all delivery services.
     */
    public function servicesIndex(Request $request): JsonResponse
    {

        $query = Service::query()->with(['providers:id,name,email,phone,department,is_active']);

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
     * Active users that can be assigned as shipping-service providers.
     */
    public function providerOptions(): JsonResponse
    {
        return response()->json(User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'department']));
    }

    /**
     * Create a new shipping service.
     */
    public function storeService(Request $request): JsonResponse
    {

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:services,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'provider_user_ids' => 'nullable|array',
            'provider_user_ids.*' => 'integer|exists:users,id',
        ]);

        $service = Service::create(collect($validated)->except('provider_user_ids')->all());
        $service->providers()->sync($validated['provider_user_ids'] ?? []);
        $service->load('providers:id,name,email,phone,department,is_active');

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

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:services,code,' . $service->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'provider_user_ids' => 'nullable|array',
            'provider_user_ids.*' => 'integer|exists:users,id',
        ]);

        $service->update(collect($validated)->except('provider_user_ids')->all());
        $service->providers()->sync($validated['provider_user_ids'] ?? []);
        $service->load('providers:id,name,email,phone,department,is_active');

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

        $service->delete();

        return response()->json([
            'message' => 'Shipping Service deleted successfully.'
        ]);
    }

    /**
     * Handle bulk actions for shipping services.
     */
    public function servicesBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:services,id',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];

        if ($action === 'activate') {
            Service::whereIn('id', $ids)->update(['is_active' => true]);
        } elseif ($action === 'deactivate') {
            Service::whereIn('id', $ids)->update(['is_active' => false]);
        } elseif ($action === 'delete') {
            Service::whereIn('id', $ids)->delete();
        }

        return response()->json([
            'message' => 'Bulk action completed successfully.',
        ]);
    }
}
