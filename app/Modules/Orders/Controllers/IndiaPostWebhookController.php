<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Shipment;
use App\Modules\Orders\Models\ShipmentTrackingEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IndiaPostWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Webhook payload from India Post
        $payload = $request->all();

        Log::info('India Post Webhook Received:', $payload);

        $articles = $payload['articles'] ?? [];
        if (empty($articles)) {
            if (isset($payload['article_number'])) {
                $articles = [$payload];
            } else {
                return response()->json(['error' => 'Invalid payload'], 400);
            }
        }

        foreach ($articles as $articleData) {
            $trackingNumber = $articleData['article_number'] ?? $articleData['articleId'] ?? null;
            $eventCode = $articleData['event_code'] ?? $articleData['evntCode'] ?? null;
            
            if (!$trackingNumber || !$eventCode) {
                continue;
            }

            $eventDescription = $articleData['event_description'] ?? $articleData['evntDesc'] ?? '';
            $eventDate = $articleData['event_date'] ?? $articleData['evntDate'] ?? null;
            $eventTime = $articleData['event_time'] ?? $articleData['evntTime'] ?? null;
            $location = $articleData['event_office_name'] ?? $articleData['officeName'] ?? 'Unknown Location';

            $shipment = Shipment::where('tracking_no', $trackingNumber)
                ->where('carrier_name', 'India Post')
                ->first();

            if (! $shipment) {
                Log::warning("Shipment not found for tracking number: {$trackingNumber}");
                continue;
            }

            // Map India Post Event Code to our internal statuses
            $statusMap = [
                'ITEM_DELIVERED' => 'delivered',
                'DELIVERED' => 'delivered',
                'BAG_CLOSE' => 'in_transit',
                'ITEM_BOOK' => 'in_transit',
                // add more mappings as per India Post documentation
            ];

            $newStatus = $statusMap[$eventCode] ?? 'in_transit';

            // Only update if it's progressing logically (simplified)
            if ($newStatus === 'delivered' && $shipment->status !== 'delivered') {
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
                    $timestamp = Carbon::parse($eventDate.' '.$eventTime);
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
        }

        return response()->json(['success' => true]);
    }
}
