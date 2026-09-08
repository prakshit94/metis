<?php
$file = 'app/Services/Shipping/Providers/IndiaPostProvider.php';
$content = file_get_contents($file);

$apiRequestHelper = <<<PHP

    protected function apiRequest(string \$method, string \$url, array \$data = [], array \$options = [])
    {
        \$token = \$this->authenticate();
        \$client = \$this->httpClient()->withToken(\$token);

        if (isset(\$options['file_path'])) {
            \$client->attach('file', file_get_contents(\$options['file_path']), basename(\$options['file_path']));
        }

        \$response = \$method === 'get' ? \$client->get(\$url, \$data) : \$client->post(\$url, \$data);

        // Auto-refresh token if expired (401 Unauthorized)
        if (\$response->status() === 401 || \$response->status() === 403) {
            \Illuminate\Support\Facades\Cache::forget('india_post_access_token');
            \$token = \$this->authenticate();
            \$client = \$this->httpClient()->withToken(\$token);
            
            if (isset(\$options['file_path'])) {
                \$client->attach('file', file_get_contents(\$options['file_path']), basename(\$options['file_path']));
            }
            
            \$response = \$method === 'get' ? \$client->get(\$url, \$data) : \$client->post(\$url, \$data);
        }

        return \$response;
    }

PHP;

// 1. Insert helper right after authenticate()
$content = preg_replace('/(\s+public function getPincodeDetails)/', $apiRequestHelper . '$1', $content);

// 2. Replace getPincodeDetails
$content = preg_replace(
    '/\$token = \$this->authenticate\(\);\s*\$baseUrl = str_replace\(\'beextcustomer\', \'bemasterdata\', \$this->baseUrl\);\s*\$response = \$this->httpClient\(\)->withToken\(\$token\)->get\("\{\$baseUrl\}\/v1\/offices\/limited-details", \[([^\]]+)\]\);/s',
    '$baseUrl = str_replace(\'beextcustomer\', \'bemasterdata\', $this->baseUrl);' . "\n\n        " . '$response = $this->apiRequest(\'get\', "{$baseUrl}/v1/offices/limited-details", [$1]);',
    $content
);

// 3. Replace getTariff
$content = preg_replace(
    '/\$token = \$this->authenticate\(\);\s*(\$endpoint = [^;]+;[^\}]+\})\s*\$response = \$this->httpClient\(\)->withToken\(\$token\)->get\("\{\$this->baseUrl\}\{\$endpoint\}", \$packageDetails\);/s',
    '$1' . "\n\n        " . '$response = $this->apiRequest(\'get\', "{$this->baseUrl}{$endpoint}", $packageDetails);',
    $content
);

// 4. Replace createShipment
$content = preg_replace(
    '/\$token = \$this->authenticate\(\);\s*(\$customId = [^;]+;)/',
    '$1',
    $content
);
$content = preg_replace(
    '/\$response = \$this->httpClient\(\)->withToken\(\$token\)\s*->post\("\{\$this->baseUrl\}\/process-articles\/\{\$customId\}", \$payload\);/s',
    '$response = $this->apiRequest(\'post\', "{$this->baseUrl}/process-articles/{$customId}", $payload);',
    $content
);

// 5. Replace createShipmentBatch
$content = preg_replace(
    '/\$token = \$this->authenticate\(\);\s*(\$customId = [^;]+;)\s*\$response = \$this->httpClient\(\)->withToken\(\$token\)\s*->attach\(\'file\', file_get_contents\(\$filePath\), basename\(\$filePath\)\)\s*->post\("\{\$this->baseUrl\}\/process-articles-file\/\{\$customId\}"\);/s',
    '$1' . "\n\n        " . '$response = $this->apiRequest(\'post\', "{$this->baseUrl}/process-articles-file/{$customId}", [], [\'file_path\' => $filePath]);',
    $content
);

// 6. Replace generateLabel
$content = preg_replace(
    '/\$token = \$this->authenticate\(\);\s*(\$payload = [^;]+;)\s*\$response = \$this->httpClient\(\)\s*->withToken\(\$token\)->post\("\{\$this->baseUrl\}\/v1\/label\/create\/domestic", \$payload\);/s',
    '$1' . "\n\n        " . '$response = $this->apiRequest(\'post\', "{$this->baseUrl}/v1/label/create/domestic", $payload);',
    $content
);

// 7. Replace trackShipment
$content = preg_replace(
    '/\$token = \$this->authenticate\(\);\s*\$response = \$this->httpClient\(\)->withToken\(\$token\)->post\("\{\$this->baseUrl\}\/v1\/tracking\/bulk", \[\s*\'barcode_no\' => \[\$trackingNumber\],\s*\]\);/s',
    '$response = $this->apiRequest(\'post\', "{$this->baseUrl}/v1/tracking/bulk", [\'barcode_no\' => [$trackingNumber]]);',
    $content
);


file_put_contents($file, $content);
echo "Patched successfully\n";
