<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Shipment;
use App\Modules\Orders\Models\ShipmentTrackingEvent;
use App\Services\CarrierTrackingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IndiaPostWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = (string) config('shipping.providers.india_post.webhook_token');
        abort_if($secret === '', 503, 'Webhook authentication is not configured.');
        abort_unless(hash_equals($secret, (string) $request->bearerToken()), 401);
        $request->validate(['articles' => 'sometimes|array|max:500', 'articles.*' => 'array']);
        // Webhook payload from India Post
        $payload = $request->all();

        Log::info('Authenticated India Post webhook received.');

        $articles = $payload['articles'] ?? [];
        if (empty($articles)) {
            if (isset($payload['article_number'])) {
                $articles = [$payload];
            } else {
                return response()->json(['error' => 'Invalid payload'], 400);
            }
        }

        foreach ($articles as $articleData) {
            if (! is_array($articleData)) {
                Log::warning('India Post webhook contained a non-object article event.');

                continue;
            }
            Validator::make($articleData, [
                'article_number' => 'sometimes|string|max:100', 'articleId' => 'sometimes|string|max:100',
                'event_code' => 'sometimes|string|max:100', 'evntCode' => 'sometimes|string|max:100',
                'event_date' => 'nullable|string|max:100', 'event_time' => 'nullable|string|max:100',
                'evntDate' => 'nullable|string|max:100', 'evntTime' => 'nullable|string|max:100',
                'event_description' => 'nullable|string', 'evntDesc' => 'nullable|string',
                'event_office_name' => 'nullable|string|max:255', 'officeName' => 'nullable|string|max:255',
            ])->validate();
            $trackingNumber = $articleData['article_number'] ?? $articleData['articleId'] ?? null;
            $eventCode = $articleData['event_code'] ?? $articleData['evntCode'] ?? null;

            if (! $trackingNumber || ! $eventCode) {
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

            $eventKey = hash('sha256', json_encode([$shipment->id, $eventCode, $eventDate, $eventTime, $location, $eventDescription]));
            app(CarrierTrackingService::class)->apply($shipment, $newStatus, $timestamp);
            DB::transaction(function () use ($shipment, $eventCode, $timestamp, $eventDescription, $location, $articleData, $eventKey) {
                Shipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();
                if (ShipmentTrackingEvent::where('provider_event_key', $eventKey)->exists()) {
                    return;
                }
                ShipmentTrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'provider_event_key' => $eventKey,
                    'event_name' => $eventDescription ?: ('India Post Event: '.$eventCode),
                    'location' => $location,
                    'description' => $eventDescription,
                    'occurred_at' => $timestamp,
                    'provider' => 'india_post',
                    'provider_event_code' => $eventCode,
                    'provider_office_id' => $articleData['event_office_facility_id'] ?? null,
                    'provider_remarks' => $articleData['non_delivery_reason'] ?? null,
                    'provider_payload' => $articleData,
                ]);
            });
        }

        return response()->json(['success' => true]);
    }
}
