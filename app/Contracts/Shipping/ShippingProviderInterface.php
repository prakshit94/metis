<?php

namespace App\Contracts\Shipping;

use App\Modules\Orders\Models\Order;

interface ShippingProviderInterface
{
    /**
     * Authenticate with the shipping provider.
     * @return string Access token or session key
     */
    public function authenticate(): string;

    /**
     * Calculate tariff for a given package.
     * @param array $packageDetails
     * @return array
     */
    public function getTariff(array $packageDetails): array;

    /**
     * Create a shipment booking for an order.
     * @param Order $order
     * @return array Should contain 'tracking_number' and any provider-specific 'label_url'
     */
    public function createShipment(Order $order): array;

    /**
     * Generate the shipping label PDF or URL.
     * @param string $trackingNumber
     * @return string
     */
    public function generateLabel(string $trackingNumber): string;

    /**
     * Get tracking status updates for given tracking numbers.
     * @param array $trackingNumbers
     * @return array
     */
    public function getTrackingStatus(array $trackingNumbers): array;
}
