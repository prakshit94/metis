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
            ->where('status', 'in_transit')
            ->whereNotNull('tracking_no')
            ->get();

        if ($shipments->isEmpty()) {
            $this->info('No in-transit shipments to track.');

            return;
        }

        $trackingNumbers = $shipments->pluck('tracking_no')->toArray();
        $provider = $shippingManager->driver('india_post');

        try {
            // Bulk API allows up to 500 at a time, we'd chunk this in reality
            $trackingData = $provider->getTrackingStatus($trackingNumbers);

            foreach ($trackingData as $data) {
                $trackingNumber = $data['booking_details']['article_number'];
                $deliveryStatus = $data['del_status']['del_status'] ?? null;
                $events = $data['tracking_details'] ?? [];

                $shipment = $shipments->firstWhere('tracking_no', $trackingNumber);
                if (! $shipment) {
                    continue;
                }

                // Record the latest event
                if (! empty($events)) {
                    $latestEvent = end($events);
                    $eventDesc = $latestEvent['event'] ?? 'Update';

                    // Simple deduplication logic
                    $exists = ShipmentTrackingEvent::where('shipment_id', $shipment->id)
                        ->where('description', $eventDesc)
                        ->exists();

                    if (! $exists) {
                        ShipmentTrackingEvent::create([
                            'shipment_id' => $shipment->id,
                            'status' => 'in_transit',
                            'location' => $latestEvent['office'] ?? 'Unknown',
                            'description' => $eventDesc,
                            'tracked_at' => now(),
                        ]);
                    }
                }

                // If delivered, update the status
                if ($deliveryStatus === 'delivered' && $shipment->status !== 'delivered') {
                    $shipment->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);
                    $shipment->order->update(['status' => 'delivered']);
                    $this->info("Shipment {$trackingNumber} marked as delivered.");
                }
            }

            $this->info('Tracking polling completed successfully.');

        } catch (\Exception $e) {
            Log::error('India Post Polling Error: '.$e->getMessage());
            $this->error('Failed to poll tracking: '.$e->getMessage());
        }
    }
}
