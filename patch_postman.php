<?php
$file = 'postman/Metis-API-Collection.json';
$json = json_decode(file_get_contents($file), true);

function traverse(&$array) {
    if (is_array($array)) {
        foreach ($array as $key => &$value) {
            if (isset($value['request']) && isset($value['request']['url']['raw'])) {
                $url = $value['request']['url']['raw'];
                $method = $value['request']['method'];
                
                if (str_contains($url, 'api/promotions/offers') && ($method === 'POST' || $method === 'PATCH')) {
                    if (isset($value['request']['body']['raw'])) {
                        $body = json_decode($value['request']['body']['raw'], true);
                        if (is_array($body)) {
                            if (!isset($body['type'])) $body['type'] = 'order_discount';
                            if (!isset($body['applicable_categories'])) $body['applicable_categories'] = [];
                            if (!isset($body['product_id'])) $body['product_id'] = null;
                            
                            // Let's add comments or just the fields so it documents it
                            $value['request']['body']['raw'] = json_encode($body, JSON_PRETTY_PRINT);
                        }
                    }
                }
                
                if (str_contains($url, 'api/promotions/coupons') && ($method === 'POST' || $method === 'PATCH')) {
                    if (isset($value['request']['body']['raw'])) {
                        $body = json_decode($value['request']['body']['raw'], true);
                        if (is_array($body)) {
                            if (!isset($body['free_product_id'])) $body['free_product_id'] = null;
                            if (!isset($body['free_qty'])) $body['free_qty'] = null;
                            if (!isset($body['applicable_categories'])) $body['applicable_categories'] = [];
                            if (!isset($body['applicable_products'])) $body['applicable_products'] = [];
                            
                            $value['request']['body']['raw'] = json_encode($body, JSON_PRETTY_PRINT);
                        }
                    }
                }
            }
            traverse($value);
        }
    }
}

traverse($json);

file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Postman collection updated successfully.\n";
