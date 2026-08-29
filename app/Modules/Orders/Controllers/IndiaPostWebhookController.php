<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Orders\Models\Shipment;
use App\Modules\Orders\Models\ShipmentTrackingEvent;
use Illuminate\Support\Facades\Log;

class IndiaPostWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Webhook payload from India Post
        $payload = $request->all();

        Log::info('India Post Webhook Received:', $payload);

        // Validate payload
        if (!isset($payload['article_number']) || !isset($payload['event_code'])) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $trackingNumber = $payload['article_number'];
        $eventCode = $payload['event_code'];
        $eventDescription = $payload['event_description'] ?? '';
        $eventDate = $payload['event_date'] ?? null;
        $eventTime = $payload['event_time'] ?? null;
        
        $location = $payload['event_office_name'] ?? 'Unknown Location';

        $shipment = Shipment::where('tracking_no', $trackingNumber)
                            ->where('carrier_name', 'India Post')
                            ->first();

        if (!$shipment) {
            Log::warning("Shipment not found for tracking number: {$trackingNumber}");
            return response()->json(['error' => 'Shipment not found'], 404);
        }

        // Map India Post Event Code to our internal statuses if needed
        $statusMap = [
            'ITEM_DELIVERED' => 'delivered',
            'BAG_CLOSE' => 'in_transit',
            'ITEM_BOOK' => 'in_transit',
            // add more mappings as per India Post documentation
        ];

        $newStatus = $statusMap[$eventCode] ?? 'in_transit';
        
        // Only update if it's progressing logically (simplified)
        if ($newStatus === 'delivered' && $shipment->status !== 'delivered') {
             // In a real app we would call InventoryService->deliverOrder($shipment->order)
             // But for the webhook we will just mark shipment as delivered for now
             $shipment->update([
                 'status' => 'delivered',
                 'delivered_at' => now(),
             ]);
             
             // Optionally update order status
             $shipment->order->update(['status' => 'delivered']);
        } elseif ($newStatus === 'in_transit' && $shipment->status === 'pending') {
             $shipment->update([
                 'status' => 'in_transit',
                 'shipped_at' => now(),
             ]);
             
             if ($shipment->order->status === 'ready_to_ship') {
                 $shipment->order->update(['status' => 'dispatched']);
             }
        }

        // Save tracking event
        $timestamp = null;
        if ($eventDate && $eventTime) {
             try {
                 $timestamp = \Carbon\Carbon::parse($eventDate . ' ' . $eventTime);
             } catch (\Exception $e) {
                 $timestamp = now();
             }
        } else {
             $timestamp = now();
        }

        ShipmentTrackingEvent::create([
            'shipment_id' => $shipment->id,
            'status' => $newStatus,
            'location' => $location,
            'description' => $eventDescription,
            'tracked_at' => $timestamp,
        ]);

        return response()->json(['success' => true]);
    }
}
