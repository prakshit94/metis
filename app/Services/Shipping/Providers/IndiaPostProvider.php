<?php

namespace App\Services\Shipping\Providers;

use App\Contracts\Shipping\ShippingProviderInterface;
use App\Models\SystemSetting;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndiaPostProvider implements ShippingProviderInterface
{
    protected $baseUrl;

    protected $username;

    protected $password;

    protected $activeOfficeId;

    public function __construct()
    {
        $settings = SystemSetting::where('key', 'india_post_offices')->pluck('value', 'key');

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
            $this->activeOfficeId = $activeOffice['id'] ?? null;
            $this->baseUrl = $activeOffice['api_base_url'] ?? config('shipping.providers.india_post.base_url');
            $this->username = $activeOffice['api_username'] ?? config('shipping.providers.india_post.username');
            $this->password = !empty($activeOffice['api_password']) ? decrypt($activeOffice['api_password'], false) : config('shipping.providers.india_post.password');

            config(['shipping.providers.india_post.bulk_customer_id' => $activeOffice['bulk_customer_id'] ?? config('shipping.providers.india_post.bulk_customer_id')]);
            config(['shipping.providers.india_post.contracts.SP_INLAND_DOC' => $activeOffice['contract_sp_doc'] ?? config('shipping.providers.india_post.contracts.SP_INLAND_DOC')]);
            config(['shipping.providers.india_post.contracts.SP_INLAND_PARCEL' => $activeOffice['contract_sp_parcel'] ?? config('shipping.providers.india_post.contracts.SP_INLAND_PARCEL')]);
            config(['shipping.providers.india_post.contracts.BUSINESS_PARCEL' => $activeOffice['contract_bp'] ?? config('shipping.providers.india_post.contracts.BUSINESS_PARCEL')]);
            config(['shipping.providers.india_post.contracts.24_SPEEDPOST_DOC' => $activeOffice['contract_24_sp_doc'] ?? config('shipping.providers.india_post.contracts.24_SPEEDPOST_DOC')]);
            config(['shipping.providers.india_post.contracts.24_SPP_PARSPL' => $activeOffice['contract_24_spp_parspl'] ?? config('shipping.providers.india_post.contracts.24_SPP_PARSPL')]);
            config(['shipping.providers.india_post.contracts.48_SPEEDPOST_DOC' => $activeOffice['contract_48_sp_doc'] ?? config('shipping.providers.india_post.contracts.48_SPEEDPOST_DOC')]);

            config(['shipping.providers.india_post.pickup_dropoff_office_id' => $activeOffice['pickup_dropoff_office_id'] ?? config('shipping.providers.india_post.pickup_dropoff_office_id')]);
            config(['shipping.providers.india_post.drop_off_pincode' => $activeOffice['drop_off_pincode'] ?? config('shipping.providers.india_post.drop_off_pincode')]);
            config(['shipping.providers.india_post.booking_office_name' => $activeOffice['booking_office_name'] ?? config('shipping.providers.india_post.booking_office_name')]);
            config(['shipping.providers.india_post.booking_office_pin' => $activeOffice['booking_office_pin'] ?? config('shipping.providers.india_post.booking_office_pin')]);
            
            config(['shipping.providers.india_post.barcode_prefix' => $activeOffice['barcode_prefix'] ?? 'EA']);
            config(['shipping.providers.india_post.barcode_start' => $activeOffice['barcode_start'] ?? '10000000']);
            config(['shipping.providers.india_post.barcode_end' => $activeOffice['barcode_end'] ?? '19999999']);
            config(['shipping.providers.india_post.barcode_current' => $activeOffice['barcode_current'] ?? $activeOffice['barcode_start'] ?? '10000000']);
        } else {
            // Fallbacks
            $this->baseUrl = config('shipping.providers.india_post.base_url');
            $this->username = config('shipping.providers.india_post.username');
            $this->password = config('shipping.providers.india_post.password');
            config(['shipping.providers.india_post.pickup_dropoff_office_id' => config('shipping.providers.india_post.pickup_dropoff_office_id')]);
            config(['shipping.providers.india_post.drop_off_pincode' => config('shipping.providers.india_post.drop_off_pincode')]);
            config(['shipping.providers.india_post.booking_office_name' => config('shipping.providers.india_post.booking_office_name')]);
            config(['shipping.providers.india_post.booking_office_pin' => config('shipping.providers.india_post.booking_office_pin')]);
        }
    }
    protected function httpClient()
    {
        $client = Http::withOptions(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]])->timeout(30);
        
        if (app()->environment('local', 'staging', 'testing')) {
            $client = $client->withoutVerifying();
        }
        
        return $client;
    }

    protected function tokenCacheKeys(): array
    {
        $scope = sha1($this->baseUrl.'|'.$this->username);
        return ['india_post_access_token_'.$scope, 'india_post_refresh_token_'.$scope];
    }

    public function clearAccessToken(): void
    {
        [$accessCacheKey] = $this->tokenCacheKeys();
        Cache::forget($accessCacheKey);
        Cache::forget('india_post_access_token');
    }

    public function authenticate(bool $forceRefresh = false): string
    {
        [$accessCacheKey, $refreshCacheKey] = $this->tokenCacheKeys();
        $accessToken = Cache::get($accessCacheKey);
        $refreshToken = Cache::get($refreshCacheKey);
        if (! $forceRefresh && $accessToken) {
            return $accessToken;
        }

        if ($refreshToken) {
            try {
                $response = $this->httpClient()->withToken($refreshToken)
                    ->post("{$this->baseUrl}/v1/access/TokenWithRtoken");
                if ($response->successful() && $response->json('success') && $response->json('data.access_token')) {
                    $this->cacheTokens($response->json('data'), $accessCacheKey, $refreshCacheKey);
                    return $response->json('data.access_token');
                }
                Log::warning('India Post refresh token rejected; falling back to login', ['status' => $response->status()]);
            } catch (\Throwable $e) {
                Log::warning('India Post refresh request failed; falling back to login', ['message' => $e->getMessage()]);
            }
        }

        try {
            $response = $this->httpClient()->post("{$this->baseUrl}/v1/access/login", [
                'username' => $this->username,
                'password' => $this->password,
            ]);
            if ($response->successful() && $response->json('success') && $response->json('data.access_token')) {
                $this->cacheTokens($response->json('data'), $accessCacheKey, $refreshCacheKey);
                return $response->json('data.access_token');
            }
            throw new \Exception('Failed to authenticate with India Post: '.$response->body());
        } catch (\Throwable $e) {
            Log::error('India Post authentication failed', ['message' => $e->getMessage(), 'base_url' => $this->baseUrl]);
            throw $e;
        }
    }

    protected function cacheTokens(array $tokens, string $accessCacheKey, string $refreshCacheKey): void
    {
        $accessToken = $tokens['access_token'] ?? null;
        if (! $accessToken) {
            throw new \UnexpectedValueException('India Post returned no access token.');
        }
        $expiresIn = (int) ($tokens['expires_in'] ?? 780);
        Cache::put($accessCacheKey, $accessToken, now()->addSeconds(max(60, min($expiresIn - 30, 840))));
        if (! empty($tokens['refresh_token'])) {
            Cache::put($refreshCacheKey, $tokens['refresh_token'], now()->addSeconds(max(60, (int) ($tokens['refresh_expires_in'] ?? 86400))));
        }
    }
    protected function apiRequest(string $method, string $url, array $data = [], array $options = [])
    {
        $token = $this->authenticate();
        $client = $this->httpClient()->withToken($token);

        if (isset($options['file_path'])) {
            $client->attach('file', file_get_contents($options['file_path']), basename($options['file_path']));
        }

        $response = $method === 'get' ? $client->get($url, $data) : $client->post($url, $data);

        // Auto-refresh token if expired (401 Unauthorized)
        if ($response->status() === 401 || $response->status() === 403) {
            [$accessCacheKey] = $this->tokenCacheKeys();
            Cache::forget($accessCacheKey);
            $token = $this->authenticate(true);
            $client = $this->httpClient()->withToken($token);
            
            if (isset($options['file_path'])) {
                $client->attach('file', file_get_contents($options['file_path']), basename($options['file_path']));
            }
            
            $response = $method === 'get' ? $client->get($url, $data) : $client->post($url, $data);
        }

        if (! $response->successful() || $response->json('success') === false) {
            Log::error('India Post API request failed', [
                'method' => strtoupper($method),
                'url' => $url,
                'status' => $response->status(),
                'message' => $response->json('message'),
                'errors' => $response->json('errors'),
            ]);
        }

        return $response;
    }


    public function getPincodeDetails(string $pincode, string $officeType = 'post'): array
    {
        $baseUrl = str_replace('beextcustomer', 'bemasterdata', $this->baseUrl);

        $response = $this->apiRequest('get', "{$baseUrl}/v1/offices/limited-details", [
            'pincode' => $pincode,
            'limit' => 50,
            'office-type' => $officeType,
        ]);

        if ($response->successful()) {
            $offices = $response->json();
            if (is_array($offices)) {
                return $offices;
            }
            Log::error('India Post pincode lookup returned a non-array response', ['pincode' => $pincode]);
            throw new \UnexpectedValueException('India Post pincode lookup returned an invalid response.');
        }

        throw new \Exception('Failed to fetch pincode details: '.$response->body());
    }

    public function getTariff(array $packageDetails): array
    {
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

        $response = $this->apiRequest('get', "{$this->baseUrl}{$endpoint}", $packageDetails);

        if ($response->successful() && $response->json('success')) {
            $tariff = $response->json();
            if (is_array($tariff)) {
                return $tariff;
            }
            Log::error('India Post tariff API returned an invalid response', ['product_code' => $packageDetails['product-code'] ?? null]);
            throw new \UnexpectedValueException('India Post tariff API returned an invalid response.');
        }

        throw new \Exception('Failed to calculate tariff: '.$response->body());
    }

    public function createShipment(Order $order): array
    {
        // Prevent N+1 issues when gathering details
        $order->loadMissing(['items.product', 'warehouse', 'party']);

        $customId = config('shipping.providers.india_post.bulk_customer_id');

        $totalWeightG = 0;
        // Current automatic booking is Business Parcel, whose documented minimum
        // dimensions are 14 x 9 x 1 cm.
        $maxLength = 14;
        $maxWidth = 9;
        $maxHeight = 1;

        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product) {
                $totalWeightG += ($product->weight_g ?: 500) * $item->quantity;
                $maxLength = max($maxLength, $product->length_cm ?: 10);
                $maxWidth = max($maxWidth, $product->width_cm ?: 10);
                // The order does not carry packed-carton dimensions. Summing product
                // heights creates invalid dimensions for multi-quantity orders; use
                // the largest known item dimension as a conservative package input.
                $maxHeight = max($maxHeight, $product->height_cm ?: 10);
            }
        }
        // Cap weight at 35000g (35kg) to respect India Post's maximum limit for BUSINESS_PARCEL
        $totalWeightG = (int) min(35000, max(10, $totalWeightG));

        // Determine contract and article type based on order logic or default to BP
        $articleType = 'BP';
        $contractId = config('shipping.providers.india_post.contracts.BUSINESS_PARCEL');

        // Shape of article
        $shape = 'NROL';
        if (in_array($articleType, ['SP', 'SP_INLAND_DOC', '24_SPEEDPOST_DOC', '48_SPEEDPOST_DOC'])) {
            $shape = 'DOC';
        }

        $otp = 'FALSE';
        if ($articleType === '24_SPP_PARSPL') {
            $otp = 'TRUE';
        }

        // Dynamic Sender info from Origin Warehouse
        $warehouse = $order->warehouse;
        $senderName = $warehouse ? $warehouse->name : config('app.name');
        $senderCompany = $warehouse ? ($warehouse->company_name ?: $senderName) : config('app.name');
        $senderPhoneRaw = $warehouse->phone ?? '';
        $senderPhoneNum = preg_replace('/[^0-9]/', '', $senderPhoneRaw);
        if (strlen($senderPhoneNum) > 10) $senderPhoneNum = substr($senderPhoneNum, -10);
        $senderPhone = $senderPhoneNum;
        
        $senderAddr = $warehouse->address_line_1 ?? '';
        $senderCity = $warehouse->city ?? '';
        $senderPin = (string) ($warehouse->pincode ?? '');

        // Dynamic Receiver info
        $receiverName = trim(($order->party->firstname ?? '') . ' ' . ($order->party->lastname ?? ''));
        $receiverName = trim($receiverName);
        
        $receiverCompany = trim($order->party->company_name ?? '');
        if (!$receiverCompany) $receiverCompany = $receiverName;
        
        $receiverPhoneRaw = $order->party->phone ?? '';
        $receiverPhoneNum = preg_replace('/[^0-9]/', '', $receiverPhoneRaw);
        if (strlen($receiverPhoneNum) > 10) $receiverPhoneNum = substr($receiverPhoneNum, -10);
        $receiverPhone = $receiverPhoneNum;
        
        $receiverAddr = $order->shipping_address_line_1 ?? '';
        $receiverCity = $order->shipping_city ?? '';
        $receiverPin = (string) ($order->shipping_pincode ?? '');

        $requiredFields = [
            'sender name' => $senderName,
            'sender address' => $senderAddr,
            'sender city' => $senderCity,
            'sender state' => $warehouse->state ?? '',
            'sender pincode' => $senderPin,
            'receiver name' => $receiverName,
            'receiver address' => $receiverAddr,
            'receiver city' => $receiverCity,
            'receiver state' => $order->shipping_state ?? '',
            'receiver pincode' => $receiverPin,
            'pickup/dropoff office ID' => (string) config('shipping.providers.india_post.pickup_dropoff_office_id'),
            'drop-off pincode' => (string) config('shipping.providers.india_post.drop_off_pincode'),
            'contract ID' => (string) $contractId,
            'bulk customer ID' => (string) $customId,
        ];
        foreach ($requiredFields as $label => $value) {
            if (trim((string) $value) === '') {
                throw new \InvalidArgumentException("India Post booking requires a configured {$label}.");
            }
        }
        if (! preg_match('/^[1-9][0-9]{5}$/', $senderPin) || ! preg_match('/^[1-9][0-9]{5}$/', $receiverPin)
            || ! preg_match('/^[1-9][0-9]{5}$/', (string) config('shipping.providers.india_post.drop_off_pincode'))) {
            throw new \InvalidArgumentException('India Post sender, receiver, and drop-off pincodes must be valid six-digit pincodes.');
        }
        if (! preg_match('/^[6-9][0-9]{9}$/', $senderPhone) || ! preg_match('/^[6-9][0-9]{9}$/', $receiverPhone)) {
            throw new \InvalidArgumentException('India Post requires valid 10-digit sender and receiver mobile numbers starting with 6, 7, 8, or 9.');
        }
        if (! preg_match('/^[0-9]{8}$/', (string) config('shipping.providers.india_post.pickup_dropoff_office_id'))) {
            throw new \InvalidArgumentException('India Post pickup/dropoff office ID must be exactly eight digits.');
        }

        $dueAmount = $order->net_amount - $order->total_paid;
        $isCOD = $dueAmount > 0;

        $article = [
            'bulk_customer_id' => (string) $customId,
            'contract_id' => (string) $contractId,
            'barcode_no' => $this->generateBarcode($order->id),
            'pickup_or_dropoff' => 'DROPOFF',
            'pickup_dropoff_office_id' => (int) config('shipping.providers.india_post.pickup_dropoff_office_id'),
            'article_type' => $articleType,
            'physical_weight' => $totalWeightG,
            'shape_of_article' => $shape,
            'length' => $maxLength,
            'breadth_diameter' => $maxWidth,
            'height' => $maxHeight,
            
            'sender_name' => substr($senderName, 0, 80),
            'sender_company' => substr($senderCompany, 0, 80),
            'sender_add_line_1' => substr($senderAddr, 0, 80),
            'sender_add_line_2' => substr($warehouse->address_line_2 ?? '', 0, 80),
            'sender_city' => substr($senderCity, 0, 80),
            'sender_state' => substr($warehouse->state ?? '', 0, 80),
            'sender_pincode' => $senderPin,
            'sender_mobile_no' => $senderPhone,
            
            'receiver_name' => substr($receiverName, 0, 80),
            'receiver_company' => substr($receiverCompany, 0, 80),
            'receiver_add_line_1' => substr($receiverAddr, 0, 80),
            'receiver_add_line_2' => substr($order->shipping_address_line_2 ?? '', 0, 80),
            'receiver_city' => substr($receiverCity, 0, 80),
            'receiver_state' => substr($order->shipping_state ?? '', 0, 80),
            'receiver_pincode' => $receiverPin,
            'receiver_mobile_no' => $receiverPhone,
            
            'alt_address_flag' => 'FALSE',
            'pickup_address_flag' => 'FALSE',
            'drop_off_pincode' => (int) config('shipping.providers.india_post.drop_off_pincode'),
            'ack' => 'FALSE',
            'reg' => 'FALSE',
            'otp' => $otp,
        ];

        if ($isCOD) {
            $article['codr_cod'] = 'COD';
            $article['value_for_codr_cod'] = (float) $dueAmount;
        }

        $payload = [
            'articles' => [ $article ],
        ];

        $response = $this->apiRequest('post', "{$this->baseUrl}/process-articles/{$customId}", $payload);

        if ($response->successful() && $response->json('success')) {
            $validArticles = $response->json('valid_articles');
            if (empty($validArticles)) {
                throw new \Exception('No valid articles processed. Errors: '.json_encode($response->json('error_articles')));
            }

            return [
                'tracking_number' => $validArticles[0]['barcode_no'],
                'article_type' => $articleType,
                'provider_response' => $response->json(),
                'actual_weight_g' => $totalWeightG,
                'length_cm' => $maxLength,
                'width_cm' => $maxWidth,
                'height_cm' => $maxHeight,
                'shipping_cost' => $validArticles[0]['calculated_tariff'] ?? $validArticles[0]['tariff'] ?? null,
                'tax_components' => $validArticles[0]['taxes'] ?? $validArticles[0]['tax_components'] ?? null,
                'booking_data' => $validArticles[0],
                'estimated_delivery_date' => $validArticles[0]['estimated_delivery_date'] ?? $validArticles[0]['expected_delivery_date'] ?? null,
            ];
        }

        throw new \Exception('Failed to create shipment: '.$response->body());
    }

    public function createShipmentBatch(string $filePath): array
    {
        $customId = config('shipping.providers.india_post.bulk_customer_id');
        $response = $this->apiRequest('post', "{$this->baseUrl}/process-articles-file/{$customId}", [], ['file_path' => $filePath]);

        if ($response->successful() && $response->json('success')) {
            return $response->json();
        }

        throw new \Exception('Failed to process shipment file: '.$response->body());
    }

    public function generateLabel(Order $order, string $trackingNumber): string
    {
        $order->loadMissing(['items.product', 'warehouse', 'party']);
        $shipment = \App\Modules\Orders\Models\Shipment::where('tracking_no', $trackingNumber)->first();

        $warehouse = $order->warehouse;
        $senderName = $warehouse ? $warehouse->name : config('app.name');
        
        $receiverName = trim(($order->party->firstname ?? '') . ' ' . ($order->party->lastname ?? ''));
        if (!$receiverName) $receiverName = 'Customer';
        
        $receiverAddr = $order->shipping_address_line_1 ?? 'Receiver Addr';
        $weight = $shipment->actual_weight_g ?? 0;
        $length = $shipment->length_cm ?? 0;
        $breadth = $shipment->width_cm ?? 0;
        $height = $shipment->height_cm ?? 0;
        if (! $weight || ! $length || ! $breadth || ! $height) {
            $calculatedWeight = 0;
            foreach ($order->items as $item) {
                if (! $item->product) {
                    continue;
                }
                $calculatedWeight += ($item->product->weight_g ?: 500) * $item->quantity;
                $length = max($length, $item->product->length_cm ?: 14);
                $breadth = max($breadth, $item->product->width_cm ?: 9);
                $height = max($height, $item->product->height_cm ?: 1);
            }
            $weight = $weight ?: $calculatedWeight;
        }
        $phoneDigits = static fn ($phone) => preg_replace('/[^0-9]/', '', (string) $phone);

        $payload = [[
            'customer_id' => (int) config('shipping.providers.india_post.bulk_customer_id'),
            'channel_type' => 'E',
            'user_type' => 'R',
            'barcode_no' => $trackingNumber,
            'service_type' => $shipment->article_type ?? 'BP',
            'booking_type' => 'COMMERCIAL',
            'physical_weight' => max(1, (int) $weight),
            'article_length' => (string) max(14, $length),
            'article_breadth' => (string) max(9, $breadth),
            'article_height' => (string) max(1, $height),
            'insurance_flag' => false,
            'insurance_value' => 0,
            'recipient_name' => substr($receiverName, 0, 80),
            'recipient_addressl1' => substr($receiverAddr, 0, 80),
            'recipient_mobile' => $phoneDigits($order->party->phone ?? ''),
            'recipient_city' => $order->shipping_city ?? '',
            'recipient_pin' => (string) ($order->shipping_pincode ?? ''),
            'sender_name' => substr($senderName, 0, 80),
            'sender_mobile' => $phoneDigits($warehouse->phone ?? ''),
            'sender_addressl1' => $warehouse->address_line_1 ?? '',
            'sender_city' => $warehouse->city ?? '',
            'sender_pin' => (string) ($warehouse->pincode ?? ''),
            'delivery_office_name' => config('shipping.providers.india_post.booking_office_name'),
            'destination_pin' => (string) ($order->shipping_pincode ?? ''),
            'transmission_mode' => 'S',
            'payment_mode' => 'CO',
            'booking_office_name' => config('shipping.providers.india_post.booking_office_name'),
            'booking_office_pin' => config('shipping.providers.india_post.booking_office_pin'),
            'size' => 'A6',
            'payment_status' => 'PC',
            'identifier' => 'Domestic'
        ]];

        $response = $this->apiRequest('post', "{$this->baseUrl}/v1/label/create/domestic", $payload);

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
        if (count($trackingNumbers) > 500) {
            throw new \InvalidArgumentException('India Post bulk tracking accepts at most 500 article numbers per request.');
        }

        if (empty($trackingNumbers)) {
            return [];
        }

        $response = $this->apiRequest('post', "{$this->baseUrl}/v1/tracking/bulk", [
            'bulk' => array_values($trackingNumbers),
        ]);

        if ($response->successful() && $response->json('success')) {
            $tracking = $response->json('data');
            if (is_array($tracking)) {
                return $tracking;
            }
            Log::error('India Post tracking API returned invalid data', ['requested_count' => count($trackingNumbers)]);
            throw new \UnexpectedValueException('India Post tracking API returned invalid data.');
        }

        throw new \Exception('Failed to get tracking status: '.$response->body());
    }

    private function generateBarcode(?int $orderId = null): string
    {
        $officeId = $this->activeOfficeId ?: 'default';
        $range = \App\Models\IndiaPostBarcodeRange::where('office_id', $officeId)
            ->where('status', 'active')
            ->orderBy('id', 'asc')
            ->first();

        if (!$range) {
            if (app()->environment('production')) {
                throw new \RuntimeException('No India Post barcode range is configured for the active office.');
            }
            // Fallback for backwards compatibility or missing setup
            $prefix = strtoupper(config('shipping.providers.india_post.barcode_prefix', 'EA'));
            $start = (int) config('shipping.providers.india_post.barcode_start', '10000000');
            $current = (int) config('shipping.providers.india_post.barcode_current', $start);
            $end = (int) config('shipping.providers.india_post.barcode_end', '19999999');
            $rangeId = null;
        } else {
            $prefix = strtoupper($range->prefix);
            $start = $range->start_sequence;
            $current = $range->current_sequence;
            $end = $range->end_sequence;
            $rangeId = $range->id;
        }

        if (!$current) {
            $current = $start;
        }

        if (strlen($prefix) !== 2 || $current < $start || $current > $end || $start < 0 || $end > 99999999) {
            throw new \RuntimeException('The configured India Post barcode sequence is outside its assigned range.');
        }

        $sequence = str_pad((string) $current, 8, '0', STR_PAD_LEFT);

        $weights = [8, 6, 4, 2, 3, 5, 9, 7];
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += ((int) $sequence[$i]) * $weights[$i];
        }
        $mod = 11 - ($sum % 11);
        if ($mod === 10) {
            $checkDigit = '0';
        } elseif ($mod === 11) {
            $checkDigit = '5';
        } else {
            $checkDigit = (string) $mod;
        }

        $barcode = $prefix . $sequence . $checkDigit . 'IN';

        $nextSequence = (int) $current + 1;
        
        if ($range) {
            if ($nextSequence > $end) {
                $range->status = 'exhausted';
                $range->current_sequence = $end; // Max it out
                $range->save();
                
                // Activate next queued range if available
                $nextRange = \App\Models\IndiaPostBarcodeRange::where('office_id', $officeId)
                    ->where('status', 'queued')
                    ->orderBy('id', 'asc')
                    ->first();
                if ($nextRange) {
                    $nextRange->status = 'active';
                    $nextRange->save();
                }
            } else {
                $range->current_sequence = $nextSequence;
                $range->save();
            }
        } else {
            // Legacy config update
            if ($nextSequence > (int) $end) {
                throw new \RuntimeException('The configured India Post barcode range is exhausted.');
            }
            $this->updateBarcodeSequence((string) $nextSequence);
            config(['shipping.providers.india_post.barcode_current' => (string) $nextSequence]);
        }

        try {
            \App\Models\IndiaPostAwbTracking::create([
                'range_id' => $rangeId,
                'office_id' => $officeId,
                'barcode' => $barcode,
                'order_id' => $orderId,
                'status' => 'used',
            ]);
        } catch (\Exception $e) {
            // Silently fail if DB is unavailable so we don't break shipment flow
        }

        return $barcode;
    }

    private function updateBarcodeSequence(string $nextSequence): void
    {
        if (!$this->activeOfficeId) return;

        $setting = SystemSetting::where('key', 'india_post_offices')->first();
        if ($setting) {
            $offices = json_decode($setting->value, true);
            if (is_array($offices)) {
                foreach ($offices as &$office) {
                    if (isset($office['id']) && $office['id'] == $this->activeOfficeId) {
                        $office['barcode_current'] = $nextSequence;
                        break;
                    }
                }
                $setting->value = json_encode($offices);
                $setting->save();
            }
        }
    }
}


