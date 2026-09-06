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
        $settings ??= SystemSetting::where('key', 'india_post_offices')->pluck('value', 'key');

        $offices = isset($settings['india_post_offices']) ? json_decode($settings['india_post_offices'], true) : [];
        if (!is_array($offices)) {
            $offices = [];
        }

        $activeOffice = null;
        // Find default or first active
        foreach ($offices as $office) {
            if (isset($office['is_default']) && $office['is_default'] && (isset($office['status']) && $office['status'] === 'active')) {
                $activeOffice = $office;
                break;
            }
        }
        if (!$activeOffice) {
            foreach ($offices as $office) {
                if (isset($office['status']) && $office['status'] === 'active') {
                    $activeOffice = $office;
                    break;
                }
            }
        }

        if ($activeOffice) {
            $this->baseUrl = $activeOffice['api_base_url'] ?? config('shipping.providers.india_post.base_url');
            $this->username = $activeOffice['api_username'] ?? config('shipping.providers.india_post.username');
            $this->password = !empty($activeOffice['api_password']) ? decrypt($activeOffice['api_password']) : config('shipping.providers.india_post.password');

            config(['shipping.providers.india_post.bulk_customer_id' => $activeOffice['bulk_customer_id'] ?? config('shipping.providers.india_post.bulk_customer_id')]);
            config(['shipping.providers.india_post.contracts.SP_INLAND_DOC' => $activeOffice['contract_sp_doc'] ?? config('shipping.providers.india_post.contracts.SP_INLAND_DOC')]);
            config(['shipping.providers.india_post.contracts.SP_INLAND_PARCEL' => $activeOffice['contract_sp_parcel'] ?? config('shipping.providers.india_post.contracts.SP_INLAND_PARCEL')]);
            config(['shipping.providers.india_post.contracts.BUSINESS_PARCEL' => $activeOffice['contract_bp'] ?? config('shipping.providers.india_post.contracts.BUSINESS_PARCEL')]);
            config(['shipping.providers.india_post.contracts.24_SPEEDPOST_DOC' => $activeOffice['contract_24_sp_doc'] ?? config('shipping.providers.india_post.contracts.24_SPEEDPOST_DOC')]);
            config(['shipping.providers.india_post.contracts.24_SPP_PARSPL' => $activeOffice['contract_24_spp_parspl'] ?? config('shipping.providers.india_post.contracts.24_SPP_PARSPL')]);
            config(['shipping.providers.india_post.contracts.48_SPEEDPOST_DOC' => $activeOffice['contract_48_sp_doc'] ?? config('shipping.providers.india_post.contracts.48_SPEEDPOST_DOC')]);

            config(['shipping.providers.india_post.pickup_dropoff_office_id' => $activeOffice['pickup_dropoff_office_id'] ?? '21260024']);
            config(['shipping.providers.india_post.drop_off_pincode' => $activeOffice['drop_off_pincode'] ?? '600001']);
            config(['shipping.providers.india_post.booking_office_name' => $activeOffice['booking_office_name'] ?? 'Default Booking Office']);
            config(['shipping.providers.india_post.booking_office_pin' => $activeOffice['booking_office_pin'] ?? '600001']);
        } else {
            // Fallbacks
            $this->baseUrl = config('shipping.providers.india_post.base_url');
            $this->username = config('shipping.providers.india_post.username');
            $this->password = config('shipping.providers.india_post.password');
            config(['shipping.providers.india_post.pickup_dropoff_office_id' => config('shipping.providers.india_post.pickup_dropoff_office_id', '21260024')]);
            config(['shipping.providers.india_post.drop_off_pincode' => config('shipping.providers.india_post.drop_off_pincode', '600001')]);
            config(['shipping.providers.india_post.booking_office_name' => config('shipping.providers.india_post.booking_office_name', 'Default Booking Office')]);
            config(['shipping.providers.india_post.booking_office_pin' => config('shipping.providers.india_post.booking_office_pin', '600001')]);
        }
    }
    protected function httpClient()
    {
        $client = Http::withOptions(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]]);
        
        if (app()->environment('local', 'staging', 'testing')) {
            $client = $client->withoutVerifying();
        }
        
        return $client;
    }

    public function authenticate(): string
    {
        $cacheKey = 'india_post_access_token';

        $fetchToken = function () {
            $response = $this->httpClient()->post("{$this->baseUrl}/v1/access/login", [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful() && $response->json('success')) {
                return $response->json('data.access_token');
            }

            throw new \Exception('Failed to authenticate with India Post: '.$response->body());
        };

        try {
            return Cache::remember($cacheKey, now()->addMinutes(14), $fetchToken);
        } catch (\Throwable $e) {
            // In case of unserialize errors or corrupted cache, clear it and retry
            Cache::forget($cacheKey);
            return Cache::remember($cacheKey, now()->addMinutes(14), $fetchToken);
        }
    }

    public function getTariff(array $packageDetails): array
    {
        $token = $this->authenticate();

        $endpoint = '/v1/speed-post/tariffs';
        if (isset($packageDetails['product-code'])) {
            switch ($packageDetails['product-code']) {
                case 'BP':
                    $endpoint = '/v1/business-parcel-tariff/calculate';
                    break;
                case '24_SPEEDPOST_DOC':
                case '48_SPEEDPOST_DOC':
                    $endpoint = '/v1/ddd-ndd-tariff/calculate';
                    break;
                case '24_SPP_PARSPL':
                    $endpoint = '/v1/parspl-tariff/calculate';
                    break;
            }
        }

        $response = $this->httpClient()->withToken($token)->get("{$this->baseUrl}{$endpoint}", $packageDetails);

        if ($response->successful() && $response->json('success')) {
            return $response->json();
        }

        throw new \Exception('Failed to calculate tariff: '.$response->body());
    }

    public function createShipment(Order $order): array
    {
        $token = $this->authenticate();
        $customId = config('shipping.providers.india_post.bulk_customer_id');

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
                $maxHeight += ($product->height_cm ?: 10) * $item->quantity;
            }
        }
        $totalWeightG = (int) max(10, $totalWeightG);

        // Determine contract and article type based on order logic or default to SP
        // If your order model has a 'shipping_method' or similar, you could map it here.
        // E.g., if ($order->shipping_method === 'business_parcel') ...
        // We'll default to SP_INLAND_PARCEL as a safe fallback
        $articleType = 'BUSINESS_PARCEL';
        $contractId = config('shipping.providers.india_post.contracts.BUSINESS_PARCEL');

        // Dynamic Sender info from Origin Warehouse
        $warehouse = $order->warehouse;
        $senderName = $warehouse ? $warehouse->name : config('app.name');
        $senderCompany = $warehouse ? ($warehouse->company_name ?: $senderName) : config('app.name');
        $senderPhone = $warehouse && $warehouse->phone ? $warehouse->phone : '9876543210';
        $senderPhone = preg_match('/^[0-9]{10,15}$/', $senderPhone) ? $senderPhone : '9876543210';
        $senderAddr = $warehouse ? ($warehouse->address_line_1 ?: 'HQ Address') : 'HQ Address';
        $senderCity = $warehouse ? ($warehouse->city ?: 'HQ City') : 'HQ City';
        $senderPin = $warehouse ? (int) $warehouse->pincode : 110001;

        // Dynamic Receiver info
        $receiverName = trim(($order->party->firstname ?? '') . ' ' . ($order->party->lastname ?? ''));
        if (!$receiverName) $receiverName = 'Customer';
        
        $receiverCompany = trim($order->party->company_name ?? '');
        if (!$receiverCompany) $receiverCompany = $receiverName;
        
        $receiverPhone = $order->party->phone ?? '';
        if (!preg_match('/^[6-9]\d{9}$/', $receiverPhone)) {
            $receiverPhone = '9876543210'; // Fallback to avoid API crash
        }
        
        $receiverAddr = $order->shipping_address_line_1 ?? 'Receiver Addr';
        $receiverCity = $order->shipping_city ?? 'Receiver City';
        $receiverPin = (int) ($order->shipping_pincode ?? 110001);

        $payload = [
            'articles' => [
                [
                    'bulk_customer_id' => (int) $customId,
                    'contract_id' => (int) $contractId,
                    'barcode_no' => $this->generateBarcode(),
                    'pickup_or_dropoff' => 'DROPOFF',
                    'pickup_dropoff_office_id' => (int) config('shipping.providers.india_post.pickup_dropoff_office_id'),
                    'article_type' => $articleType,
                    'physical_weight' => $totalWeightG,
                    'shape_of_article' => 'NROL',
                    'length' => (int) $maxLength,
                    'breadth_diameter' => (int) $maxWidth,
                    'height' => (int) $maxHeight,
                    
                    'sender_name' => substr($senderName, 0, 50),
                    'sender_company' => substr($senderCompany, 0, 50),
                    'sender_add_line_1' => substr($senderAddr, 0, 100),
                    'sender_city' => substr($senderCity, 0, 50),
                    'sender_pincode' => $senderPin,
                    'sender_mobile_no' => $senderPhone,
                    
                    'receiver_name' => substr($receiverName, 0, 50),
                    'receiver_company' => substr($receiverCompany, 0, 50),
                    'receiver_add_line_1' => substr($receiverAddr, 0, 100),
                    'receiver_city' => substr($receiverCity, 0, 50),
                    'receiver_pincode' => $receiverPin,
                    'receiver_mobile_no' => $receiverPhone,
                    
                    'alt_address_flag' => 'FALSE',
                    'pickup_address_flag' => 'FALSE',
                    'drop_off_pincode' => (int) config('shipping.providers.india_post.drop_off_pincode'),
                ],
            ],
        ];

        $response = $this->httpClient()->withToken($token)
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

    public function generateLabel(Order $order, string $trackingNumber): string
    {
        $token = $this->authenticate();

        $warehouse = $order->warehouse;
        $senderName = $warehouse ? $warehouse->name : config('app.name');
        
        $receiverName = trim(($order->party->firstname ?? '') . ' ' . ($order->party->lastname ?? ''));
        if (!$receiverName) $receiverName = 'Customer';
        
        $receiverAddr = $order->shipping_address_line_1 ?? 'Receiver Addr';

        $payload = [[
            'customer_id' => (int) config('shipping.providers.india_post.bulk_customer_id'),
            'channel_type' => 'E',
            'user_type' => 'R',
            'barcode_no' => $trackingNumber,
            'service_type' => 'BP',
            'booking_type' => 'COMMERCIAL',
            'recipient_name' => substr($receiverName, 0, 50),
            'recipient_addressl1' => substr($receiverAddr, 0, 100),
            'sender_name' => substr($senderName, 0, 50),
            'transmission_mode' => 'S',
            'payment_mode' => 'CO',
            'booking_office_name' => config('shipping.providers.india_post.booking_office_name'),
            'booking_office_pin' => config('shipping.providers.india_post.booking_office_pin'),
            'size' => 'A6',
            'payment_status' => 'PC',
            'identifier' => 'Domestic'
        ]];

        $response = $this->httpClient()
            ->withToken($token)->post("{$this->baseUrl}/v1/label/create/domestic", $payload);

        if ($response->successful()) {
            // Ideally we'd store the PDF somewhere and return the URL
            // Since this API returns a PDF directly (based on the document: "Sample Output: PDF file"), 
            // we should save it and return a local url.
            $filename = 'label_' . $trackingNumber . '.pdf';
            \Illuminate\Support\Facades\Storage::put('public/labels/' . $filename, $response->body());
            return url('storage/labels/' . $filename);
        }

        throw new \Exception('Failed to generate label: '.$response->body());
    }

    public function getTrackingStatus(array $trackingNumbers): array
    {
        $token = $this->authenticate();

        $response = $this->httpClient()->withToken($token)->post("{$this->baseUrl}/v1/tracking/bulk", [
            'bulk' => $trackingNumbers,
        ]);

        if ($response->successful() && $response->json('success')) {
            return $response->json('data');
        }

        throw new \Exception('Failed to get tracking status: '.$response->body());
    }

    private function generateBarcode(): string
    {
        // For testing we will generate a barcode in the correct format: XX123456789XX
        $prefix = 'ET';
        $randomNum = str_pad((string) rand(1, 999999999), 9, '0', STR_PAD_LEFT);
        $suffix = 'IN';

        return $prefix.$randomNum.$suffix;
    }
}
