<?php

namespace App\Contracts\Shipping;

use App\Modules\Orders\Models\Order;

interface ShippingProviderInterface
{
    /**
     * Authenticate with the shipping provider.
     *
     * @return string Access token or session key
     */
    public function authenticate(): string;

    /**
     * Calculate tariff for a given package.
     */
    public function getTariff(array $packageDetails): array;

    /**
     * Create a shipment booking for an order.
     *
     * @return array Should contain 'tracking_number' and any provider-specific 'label_url'
     */
    public function createShipment(Order $order): array;

    /**
     * Generate the shipping label PDF or URL.
     */
    public function generateLabel(string $trackingNumber): string;

    /**
     * Get tracking status updates for given tracking numbers.
     */
    public function getTrackingStatus(array $trackingNumbers): array;
}
