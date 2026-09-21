<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Modules\Catalog\Models\Service;
use App\Modules\Orders\Models\Shipment;
use App\Modules\Orders\Models\ShipmentTrackingEvent;
use App\Modules\Users\Models\User;
use App\Services\InventoryService;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:shipping-view', only: ['shipmentsIndex', 'trackingEvents', 'fetchLiveTracking', 'servicesIndex', 'providerOptions']),
            new Middleware('permission:shipping-create', only: ['addTrackingEvent', 'storeService']),
            new Middleware('permission:shipping-edit', only: ['updateShipmentStatus', 'updateShipment', 'updateService', 'toggleService', 'shipmentsBulk', 'servicesBulk']),
            new Middleware('permission:shipping-delete', only: ['destroyService']),
        ];
    }

    /**
     * Get paginated shipments list with filters.
     */
    public function shipmentsIndex(Request $request): JsonResponse
    {

        $query = Shipment::with(['order.items.product', 'order.party', 'order.shippingAddress', 'order.warehouse', 'service.providers:id,name,email,phone,department_id,is_active']);

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

        if ($carrier = $request->query('carrier')) {
            $query->where('carrier_name', $carrier);
        }
        
        $dateField = $request->query('date_type', 'created_at');
        if (!in_array($dateField, ['created_at', 'shipped_at', 'delivered_at'])) {
            $dateField = 'created_at';
        }

        if ($fromDate = $request->query('from_date')) {
            $query->whereDate($dateField, '>=', $fromDate);
        }

        if ($toDate = $request->query('to_date')) {
            $query->whereDate($dateField, '<=', $toDate);
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
     * Update shipment details (carrier and tracking number).
     */
    public function updateShipment(Request $request, Shipment $shipment): JsonResponse
    {
        $validated = $request->validate([
            'carrier_name' => 'required|string|max:255',
            'service_provider_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request) {
                    $carrierName = $request->input('carrier_name');
                    if ($carrierName && $value) {
                        $service = \App\Modules\Catalog\Models\Service::where('name', $carrierName)->first();
                        if (!$service || !$service->providers()->where('users.id', $value)->exists()) {
                            $fail('The selected service provider is not assigned to the selected carrier.');
                        }
                    }
                },
            ],
            'tracking_no' => 'nullable|string|max:255',
        ]);

        $oldCarrier = $shipment->carrier_name;
        $oldTracking = $shipment->tracking_no;
        $oldProviderId = $shipment->service_provider_id;

        $shipment->update([
            'carrier_name' => $validated['carrier_name'],
            'service_provider_id' => $validated['service_provider_id'] ?? null,
            'tracking_no' => $validated['tracking_no'] ?? null,
        ]);

        if ($oldCarrier !== $shipment->carrier_name || $oldTracking !== $shipment->tracking_no || $oldProviderId !== $shipment->service_provider_id) {
            ShipmentTrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_name' => 'Shipment Details Updated',
                'location' => 'System',
                'description' => "Shipment details updated. Carrier: {$oldCarrier} -> {$shipment->carrier_name}. Tracking: {$oldTracking} -> ".($shipment->tracking_no ?? 'None').". Provider ID: {$oldProviderId} -> ".($shipment->service_provider_id ?? 'None'),
                'occurred_at' => now(),
            ]);
            
            if ($shipment->order) {
                $shipment->order->statusLogs()->create([
                    'status' => $shipment->order->status,
                    'notes' => "Shipment details updated. Carrier: {$shipment->carrier_name}. Tracking: ".($shipment->tracking_no ?? 'None').". Provider ID: ".($shipment->service_provider_id ?? 'None'),
                    'changed_by' => auth()->id(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Shipment details updated successfully.',
            'shipment' => $shipment->load('order'),
        ]);
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
            'reschedule_reason' => 'nullable|string|max:255',
            'delivered_by' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($shipment, $validated) {
            $oldStatus = $shipment->status;
            $newStatus = $validated['status'] === 'shipped' ? 'in_transit' : $validated['status'];

            $updateData = ['status' => $newStatus];

            if ($newStatus === 'in_transit' && ! $shipment->shipped_at) {
                $updateData['shipped_at'] = now();
            }

            if ($newStatus === 'delivered' && ! $shipment->delivered_at) {
                $updateData['delivered_at'] = now();
            }

            if (isset($validated['delivery_attempts'])) {
                $updateData['delivery_attempts'] = $validated['delivery_attempts'];
            }
            if (isset($validated['next_followup_date'])) {
                $updateData['next_followup_date'] = $validated['next_followup_date'];
            }
            if (isset($validated['reschedule_reason'])) {
                $updateData['reschedule_reason'] = $validated['reschedule_reason'];
            }
            if (isset($validated['delivered_by'])) {
                $updateData['delivered_by'] = $validated['delivered_by'];
            } elseif ($newStatus === 'delivered') {
                $updateData['delivered_by'] = auth()->user()->name;
            }

            $shipment->update($updateData);

            // Create tracking event
            ShipmentTrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_name' => 'Status Updated to '.ucfirst($newStatus),
                'location' => $validated['location'] ?? 'Hub Location',
                'description' => $validated['description'] ?? "Shipment status changed from {$oldStatus} to {$newStatus}.",
                'reschedule_reason' => $validated['reschedule_reason'] ?? null,
                'occurred_at' => now(),
            ]);

            // Sync with Order Status if needed
            $order = $shipment->order;
            if ($order) {
                $inventoryService = app(InventoryService::class);
                if ($newStatus === 'in_transit' && $order->status === 'ready_to_ship') {
                    $inventoryService->dispatchOrder($order);
                } elseif ($newStatus === 'delivered' && in_array($order->status, ['dispatched', 'shipped'], true)) {
                    $inventoryService->deliverOrder($order);
                    $order->statusLogs()->create([
                        'status' => 'delivered',
                        'notes' => 'Shipment delivered.',
                        'changed_by' => auth()->id(),
                    ]);
                } elseif ($newStatus === 'returned' && ! in_array($order->status, ['returned', 'cancelled'], true) && ! request()->boolean('skip_order_sync')) {
                    $inventoryService->returnOrder($order);
                }

                // Add status log for rescheduling if applicable
                if (isset($validated['next_followup_date'])) {
                    $reasonText = isset($validated['reschedule_reason']) ? 'Reason: '.ucfirst(str_replace('_', ' ', $validated['reschedule_reason'])).'. ' : '';
                    $order->statusLogs()->create([
                        'status' => 'delivery_rescheduled',
                        'notes' => $reasonText.($validated['description'] ?? 'Scheduled for future delivery attempt.'),
                        'changed_by' => auth()->id(),
                    ]);
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
        $shipment->load([
            'order' => fn ($q) => $q->with(['statusLogs' => fn ($q) => $q->with('user')->latest()]),
        ]);

        return response()->json([
            'shipment' => $shipment,
            'events' => $shipment->events()->orderBy('occurred_at', 'desc')->get(),
        ]);
    }

    /**
     * Fetch live tracking status from India Post API for a single shipment.
     * Read-only: no DB writes, no status changes.
     */
    public function fetchLiveTracking(Shipment $shipment, ShippingManager $shippingManager): JsonResponse
    {
        if ($shipment->carrier_name !== 'India Post') {
            return response()->json([
                'error' => 'Live tracking is only available for India Post shipments.',
            ], 422);
        }

        if (! $shipment->tracking_no) {
            return response()->json([
                'error' => 'This shipment does not have a tracking number assigned yet.',
            ], 422);
        }

        try {
            $provider = $shippingManager->driver('india_post');
            $data = $provider->getTrackingStatus([$shipment->tracking_no]);

            // Find the matching entry by article number, fallback to first result
            $result = null;
            foreach ($data as $entry) {
                if (($entry['booking_details']['article_number'] ?? null) === $shipment->tracking_no) {
                    $result = $entry;
                    break;
                }
            }
            if (! $result && ! empty($data)) {
                $result = $data[0];
            }

            // India Post returned an empty response — tracking number not found in their system
            if (! $result) {
                return response()->json([
                    'error' => 'No tracking data found in India Post for tracking number: ' . $shipment->tracking_no . '. The shipment may not have been scanned yet.',
                ], 404);
            }

            return response()->json([
                'tracking_no'      => $shipment->tracking_no,
                'booking_details'  => $result['booking_details'] ?? null,
                'del_status'       => $result['del_status'] ?? null,
                'tracking_details' => $result['tracking_details'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch live tracking: ' . $e->getMessage(),
            ], 500);
        }
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

                if ($shipment->status === $newStatus) {
                    continue;
                }

                $updateData = ['status' => $newStatus];

                if ($newStatus === 'in_transit' && ! $shipment->shipped_at) {
                    $updateData['shipped_at'] = now();
                }

                if ($newStatus === 'delivered' && ! $shipment->delivered_at) {
                    $updateData['delivered_at'] = now();
                }

                $shipment->update($updateData);

                // Create tracking event
                ShipmentTrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'event_name' => 'Status Updated to '.ucfirst($newStatus).' (Bulk)',
                    'location' => 'Bulk Update',
                    'description' => "Shipment status changed to {$newStatus} via bulk action.",
                    'occurred_at' => now(),
                ]);

                // Sync with Order Status if needed
                $order = $shipment->order;
                if ($order) {
                    $inventoryService = app(InventoryService::class);
                    if ($newStatus === 'in_transit' && $order->status === 'ready_to_ship') {
                        $inventoryService->dispatchOrder($order);
                    } elseif ($newStatus === 'delivered' && in_array($order->status, ['dispatched', 'shipped'], true)) {
                        $inventoryService->deliverOrder($order);
                    } elseif ($newStatus === 'returned' && ! in_array($order->status, ['returned', 'cancelled'], true) && ! request()->boolean('skip_order_sync')) {
                        $inventoryService->returnOrder($order);
                    }
                }
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

        $query = Service::query()->with(['providers:id,name,email,phone,department_id,is_active']);

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
            ->get(['id', 'name', 'email', 'phone', 'department_id']));
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
            'provider_priorities' => 'nullable|array',
            'provider_priorities.*' => 'nullable|integer|min:1',
        ]);

        $service = Service::create(collect($validated)->except(['provider_user_ids', 'provider_priorities'])->all());
        
        $syncData = [];
        $priorities = $validated['provider_priorities'] ?? [];
        $assignedPriorities = [];
        
        foreach ($validated['provider_user_ids'] ?? [] as $providerId) {
            $pri = $priorities[$providerId] ?? 1;
            if (in_array($pri, $assignedPriorities, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'provider_priorities' => 'Duplicate priorities are not allowed for assigned providers.'
                ]);
            }
            $assignedPriorities[] = $pri;
            $syncData[$providerId] = ['priority' => $pri];
        }
        
        $service->providers()->sync($syncData);
        
        $service->load('providers:id,name,email,phone,department_id,is_active');

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
            'code' => 'required|string|max:50|unique:services,code,'.$service->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'provider_user_ids' => 'nullable|array',
            'provider_user_ids.*' => 'integer|exists:users,id',
            'provider_priorities' => 'nullable|array',
            'provider_priorities.*' => 'nullable|integer|min:1',
        ]);

        $service->update(collect($validated)->except(['provider_user_ids', 'provider_priorities'])->all());
        
        $syncData = [];
        $priorities = $validated['provider_priorities'] ?? [];
        $assignedPriorities = [];
        
        foreach ($validated['provider_user_ids'] ?? [] as $providerId) {
            $pri = $priorities[$providerId] ?? 1;
            if (in_array($pri, $assignedPriorities, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'provider_priorities' => 'Duplicate priorities are not allowed for assigned providers.'
                ]);
            }
            $assignedPriorities[] = $pri;
            $syncData[$providerId] = ['priority' => $pri];
        }
        
        $service->providers()->sync($syncData);
        
        $service->load('providers:id,name,email,phone,department_id,is_active');

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

        $service->update(['is_active' => ! $service->is_active]);

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
            'message' => 'Shipping Service deleted successfully.',
        ]);
    }

    /**
     * Handle bulk actions for shipping services.
     */
    public function servicesBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete,assign_provider',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:services,id',
            'provider_ids' => 'required_if:action,assign_provider|array|min:1',
            'provider_ids.*' => 'integer|exists:users,id',
            'provider_priorities' => 'nullable|array',
            'provider_priorities.*' => 'nullable|integer|min:1',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];

        if ($action === 'activate') {
            Service::whereIn('id', $ids)->update(['is_active' => true]);
        } elseif ($action === 'deactivate') {
            Service::whereIn('id', $ids)->update(['is_active' => false]);
        } elseif ($action === 'delete') {
            Service::whereIn('id', $ids)->delete();
        } elseif ($action === 'assign_provider') {
            $providerIds = $validated['provider_ids'];
            $priorities = $validated['provider_priorities'] ?? [];
            $syncData = [];
            $assignedPriorities = [];
            
            foreach ($providerIds as $providerId) {
                $pri = $priorities[$providerId] ?? 1;
                if (in_array($pri, $assignedPriorities, true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'provider_priorities' => 'Duplicate priorities are not allowed for assigned providers.'
                    ]);
                }
                $assignedPriorities[] = $pri;
                $syncData[$providerId] = ['priority' => $pri];
            }
            $services = Service::whereIn('id', $ids)->get();
            foreach ($services as $service) {
                $service->providers()->syncWithoutDetaching($syncData);
            }
        }

        return response()->json([
            'message' => 'Bulk action completed successfully.',
        ]);
    }
}
