<?php

namespace App\Services\Shipping\Providers;

use App\Contracts\Shipping\ShippingProviderInterface;
use App\Models\SystemSetting;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IndiaPostProvider implements ShippingProviderInterface
{
    protected $baseUrl;

    protected $username;

    protected $password;

    public function __construct()
    {
        static $settings = null;
        $settings ??= SystemSetting::where('key', 'like', 'india_post_%')->pluck('value', 'key');

        $this->baseUrl = $settings['india_post_base_url'] ?? config('shipping.providers.india_post.base_url');
        $this->username = $settings['india_post_username'] ?? config('shipping.providers.india_post.username');
        $this->password = isset($settings['india_post_password']) ? decrypt($settings['india_post_password']) : config('shipping.providers.india_post.password');

        config(['shipping.providers.india_post.bulk_customer_id' => $settings['india_post_bulk_customer_id'] ?? config('shipping.providers.india_post.bulk_customer_id')]);
        config(['shipping.providers.india_post.contracts.SP_INLAND_DOC' => $settings['india_post_contract_sp_doc'] ?? config('shipping.providers.india_post.contracts.SP_INLAND_DOC')]);
        config(['shipping.providers.india_post.contracts.SP_INLAND_PARCEL' => $settings['india_post_contract_sp_parcel'] ?? config('shipping.providers.india_post.contracts.SP_INLAND_PARCEL')]);
        config(['shipping.providers.india_post.contracts.BUSINESS_PARCEL' => $settings['india_post_contract_bp'] ?? config('shipping.providers.india_post.contracts.BUSINESS_PARCEL')]);
        config(['shipping.providers.india_post.contracts.24_SPEEDPOST_DOC' => $settings['india_post_contract_24_sp_doc'] ?? config('shipping.providers.india_post.contracts.24_SPEEDPOST_DOC')]);
        config(['shipping.providers.india_post.contracts.24_SPP_PARSPL' => $settings['india_post_contract_24_spp_parspl'] ?? config('shipping.providers.india_post.contracts.24_SPP_PARSPL')]);
        config(['shipping.providers.india_post.contracts.48_SPEEDPOST_DOC' => $settings['india_post_contract_48_sp_doc'] ?? config('shipping.providers.india_post.contracts.48_SPEEDPOST_DOC')]);
    }

    public function authenticate(): string
    {
        $cacheKey = 'india_post_access_token';

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            $response = Http::withoutVerifying()->withOptions(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]])->post("{$this->baseUrl}/v1/access/login", [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful() && $response->json('success')) {
                return $response->json('data.access_token');
            }

            throw new \Exception('Failed to authenticate with India Post: '.$response->body());
        });
    }

    public function getTariff(array $packageDetails): array
    {
        $token = $this->authenticate();

        $response = Http::withoutVerifying()->withOptions(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]])->withToken($token)->get("{$this->baseUrl}/v1/speed-post/tariffs", $packageDetails);

        if ($response->successful() && $response->json('success')) {
            return $response->json();
        }

        throw new \Exception('Failed to calculate tariff: '.$response->body());
    }

    public function createShipment(Order $order): array
    {
        $token = $this->authenticate();
        $customId = config('shipping.providers.india_post.bulk_customer_id');

        // Here we map the order to India Post's expected payload format
        // This is a simplified mapping based on the provided spec
        $totalWeightG = 0;
        $maxLength = 10;
        $maxWidth = 10;
        $maxHeight = 10;

        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product) {
                $totalWeightG += ($product->weight_g ?: 500) * $item->quantity;
                $maxLength = max($maxLength, $product->length_cm ?: 10);
                $maxWidth = max($maxWidth, $product->width_cm ?: 10);
                // Simple box logic: we stack items up, so we sum the heights
                $maxHeight += ($product->height_cm ?: 10) * $item->quantity;
            }
        }

        $totalWeightG = max(10, $totalWeightG); // Ensure at least some weight

        $payload = [
            'articles' => [
                [
                    'bulk_customer_id' => $customId,
                    'contract_id' => config('shipping.providers.india_post.contracts.SP_INLAND_PARCEL'),
                    'barcode_no' => $this->generateBarcode(), // Generating a dummy barcode for testing/sandbox
                    'pickup_or_dropoff' => 'DROPOFF',
                    'pickup_dropoff_office_id' => 21260024,
                    'article_type' => 'SP_INLAND_PARCEL',
                    'physical_weight' => $totalWeightG,
                    'shape_of_article' => 'NROL',
                    'length' => $maxLength,
                    'breadth_diameter' => $maxWidth,
                    'height' => $maxHeight,
                    'sender_name' => 'Metis Sender',
                    'sender_company' => 'Metis',
                    'sender_add_line_1' => 'Sender Addr',
                    'sender_city' => 'Sender City',
                    'sender_pincode' => 600001,
                    'receiver_name' => $order->address->first_name.' '.$order->address->last_name ?? 'Receiver Name',
                    'receiver_company' => '',
                    'receiver_add_line_1' => $order->address->address_line_1 ?? 'Receiver Addr',
                    'receiver_city' => $order->address->city ?? 'Receiver City',
                    'receiver_pincode' => $order->address->postal_code ?? 110001,
                    'alt_address_flag' => 'FALSE',
                    'pickup_address_flag' => 'FALSE',
                    'drop_off_pincode' => 600001,
                    'sender_mobile_no' => '1234567890',
                    'receiver_mobile_no' => $order->address->phone_number ?? '1234567890',
                ],
            ],
        ];

        $response = Http::withoutVerifying()->withOptions(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]])->withToken($token)
            ->post("{$this->baseUrl}/process-articles/{$customId}", $payload);

        if ($response->successful() && $response->json('success')) {
            $validArticles = $response->json('valid_articles');
            if (empty($validArticles)) {
                throw new \Exception('No valid articles processed. Errors: '.json_encode($response->json('error_articles')));
            }

            return [
                'tracking_number' => $validArticles[0]['barcode_no'],
                'provider_response' => $response->json(),
                'actual_weight_g' => $totalWeightG,
                'length_cm' => $maxLength,
                'width_cm' => $maxWidth,
                'height_cm' => $maxHeight,
                'shipping_cost' => $validArticles[0]['tariff'] ?? 0,
            ];
        }

        throw new \Exception('Failed to create shipment: '.$response->body());
    }

    public function generateLabel(string $trackingNumber): string
    {
        // Implementation for label generation
        return 'label_url';
    }

    public function getTrackingStatus(array $trackingNumbers): array
    {
        $token = $this->authenticate();

        $response = Http::withoutVerifying()->withOptions(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]])->withToken($token)->post("{$this->baseUrl}/v1/tracking/bulk", [
            'bulk' => $trackingNumbers,
        ]);

        if ($response->successful() && $response->json('success')) {
            return $response->json('data');
        }

        throw new \Exception('Failed to get tracking status: '.$response->body());
    }

    private function generateBarcode(): string
    {
        // For testing we will generate a barcode in the range ET21433001XIN to ET21434000XIN
        $prefix = 'ET21433';
        $randomNum = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $suffix = 'XIN';

        return $prefix.$randomNum.$suffix;
    }
}
