<?php

namespace App\Console\Commands;

use App\Modules\Orders\Models\Shipment;
use App\Modules\Orders\Models\ShipmentTrackingEvent;
use App\Services\Shipping\ShippingManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PollIndiaPostTracking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indiapost:poll-tracking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll India Post for tracking updates for in-transit shipments';

    /**
     * Execute the console command.
     */
    public function handle(ShippingManager $shippingManager)
    {
        // Get all in-transit shipments for India Post
        $shipments = Shipment::where('carrier_name', 'India Post')
            ->whereIn('status', ['pending', 'in_transit'])
            ->whereNotNull('tracking_no')
            ->get();

        if ($shipments->isEmpty()) {
            $this->info('No in-transit shipments to track.');

            return;
        }

        $trackingNumbers = $shipments->pluck('tracking_no')->toArray();
        $provider = $shippingManager->driver('india_post');

        try {
            // Bulk API allows up to 500 at a time
            $chunks = array_chunk($trackingNumbers, 500);

            foreach ($chunks as $chunk) {
                $trackingData = $provider->getTrackingStatus($chunk);

                foreach ($trackingData as $data) {
                    if (! is_array($data)) {
                        Log::warning('India Post tracking response contained a non-object record.');
                        continue;
                    }
                    $trackingNumber = $data['booking_details']['article_number'] ?? null;
                    if (! $trackingNumber) {
                        Log::warning('India Post tracking response omitted booking_details.article_number', ['keys' => array_keys($data)]);
                        continue;
                    }
                    $deliveryStatus = $data['del_status']['del_status'] ?? null;
                    $events = $data['tracking_details'] ?? [];

                    $shipment = $shipments->firstWhere('tracking_no', $trackingNumber);
                    if (! $shipment) {
                        continue;
                    }

                    // Preserve the full latest provider response, including booking
                    // and delivery fields that do not have dedicated local columns.
                    $shipment->update(['india_post_tracking_response' => $data]);

                    // Persist every event, idempotently, using the source event time.
                    foreach ($events as $event) {
                        if (! is_array($event)) {
                            Log::warning('India Post tracking response contained a non-object event', ['tracking_number' => $trackingNumber]);
                            continue;
                        }
                        $eventDesc = $event['event'] ?? 'Update';
                        try {
                            $occurredAt = \Carbon\Carbon::parse($event['date'] ?? now());
                            if (isset($event['time']) && ! preg_match('/T\d{2}:\d{2}/', (string) ($event['date'] ?? ''))) {
                                $occurredAt = \Carbon\Carbon::parse(($event['date'] ?? now()->toDateString()).' '.$event['time']);
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Could not parse India Post tracking event timestamp', ['tracking_number' => $trackingNumber, 'event' => $event]);
                            $occurredAt = now();
                        }

                        $exists = ShipmentTrackingEvent::where('shipment_id', $shipment->id)
                            ->where('event_name', $eventDesc)
                            ->where('location', $event['office'] ?? 'Unknown')
                            ->where('occurred_at', $occurredAt)
                            ->exists();
                        if (! $exists) {
                            ShipmentTrackingEvent::create([
                                'shipment_id' => $shipment->id,
                                'event_name' => $eventDesc,
                                'location' => $event['office'] ?? 'Unknown',
                                'description' => $event['remarks'] ?? $eventDesc,
                                'occurred_at' => $occurredAt,
                                'provider' => 'india_post',
                                'provider_event_code' => $eventDesc,
                                'provider_office_id' => $event['officeid'] ?? null,
                                'provider_remarks' => $event['remarks'] ?? null,
                                'provider_is_rts' => $event['rts'] ?? null,
                                'provider_payload' => [
                                    'booking_details' => $data['booking_details'] ?? null,
                                    'tracking_event' => $event,
                                    'del_status' => $data['del_status'] ?? null,
                                ],
                            ]);
                        }
                    }

                    if (! empty($events) && $shipment->status === 'pending' && $deliveryStatus !== 'delivered') {
                        $shipment->update(['status' => 'in_transit', 'shipped_at' => $shipment->shipped_at ?? now()]);
                        if ($shipment->order && $shipment->order->status === 'ready_to_ship') {
                            $shipment->order->update(['status' => 'dispatched']);
                        }
                    }

                    // If delivered, update the status
                    if ($deliveryStatus === 'delivered' && $shipment->status !== 'delivered') {
                        $deliveredAt = now();
                        if (! empty($data['booking_details']['delivery_confirmed_on'])) {
                            try {
                                $deliveredAt = \Carbon\Carbon::parse($data['booking_details']['delivery_confirmed_on']);
                            } catch (\Throwable $e) {
                                Log::warning('Could not parse India Post delivery confirmation timestamp', ['tracking_number' => $trackingNumber]);
                            }
                        }
                        $shipment->update([
                            'status' => 'delivered',
                            'delivered_at' => $deliveredAt,
                        ]);
                        if ($shipment->order) {
                            $shipment->order->update(['status' => 'delivered']);
                        }
                        $this->info("Shipment {$trackingNumber} marked as delivered.");
                    }
                }
            }

            $this->info('Tracking polling completed successfully.');

        } catch (\Exception $e) {
            Log::error('India Post Polling Error: '.$e->getMessage());
            $this->error('Failed to poll tracking: '.$e->getMessage());
        }
    }
}
