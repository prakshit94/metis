<?php
$file = 'app/Imports/OrdersImport.php';
$content = file_get_contents($file);

$target = "                \$shippingAdd1 = trim((string)(\$firstRow['shipping_address_1'] ?? \$firstRow['shipping_address_line_1'] ?? ''));
                if (\$shippingAdd1 === '' || \$shippingAdd1 === '-') \$shippingAdd1 = 'Default Address';

                \$address = PartyAddress::firstOrCreate(
                    [
                        'party_id' => \$customer->id,
                        'village_id' => \$village->id,
                    ],
                    [
                        'label' => 'Imported',
                        'address_line_1' => \$shippingAdd1,
                        'village_name' => \$village->village_name,
                        'district' => \$village->district_name,
                        'state' => \$village->state_name,
                    ]
                );";

$replacement = "                \$shippingAdd1 = trim((string)(\$firstRow['shipping_address_1'] ?? \$firstRow['shipping_address_line_1'] ?? ''));
                if (\$shippingAdd1 === '' || \$shippingAdd1 === '-') \$shippingAdd1 = 'Default Address';

                \$getVal = function(\$keys, \$default = null) use (\$firstRow) {
                    foreach ((array)\$keys as \$k) {
                        if (isset(\$firstRow[\$k])) {
                            \$v = trim((string)\$firstRow[\$k]);
                            if (\$v !== '' && \$v !== '-') return \$v;
                        }
                    }
                    return \$default;
                };

                \$shippingAdd2 = \$getVal(['shipping_address_2', 'shipping_address_line_2']);
                \$shippingPO = \$getVal(['shipping_pobo', 'shipping_post_office']);
                \$shippingTaluka = \$getVal(['shipping_taluka']);
                \$shippingCity = \$getVal(['shipping_city']);
                \$shippingPincode = \$getVal(['shipping_pincode']);

                \$shippingAddress = PartyAddress::firstOrCreate(
                    [
                        'party_id' => \$customer->id,
                        'village_id' => \$village->id,
                    ],
                    [
                        'label' => 'Imported Shipping',
                        'address_line_1' => \$shippingAdd1,
                        'address_line_2' => \$shippingAdd2,
                        'village_name' => \$village->village_name,
                        'post_office' => \$shippingPO,
                        'taluka' => \$shippingTaluka,
                        'district' => \$village->district_name,
                        'city' => \$shippingCity,
                        'state' => \$village->state_name,
                        'pincode' => \$shippingPincode,
                    ]
                );

                \$billingAdd1 = \$getVal(['billing_address_1', 'billing_address_line_1']);
                \$billingAdd2 = \$getVal(['billing_address_2', 'billing_address_line_2']);
                \$billingPO = \$getVal(['billing_pobo', 'billing_post_office']);
                \$billingTaluka = \$getVal(['billing_taluka']);
                \$billingCity = \$getVal(['billing_city']);
                \$billingPincode = \$getVal(['billing_pincode']);
                
                \$billingAddress = \$shippingAddress;
                if (\$billingAdd1) {
                    // Try to resolve billing village if it differs, or use shipping village
                    \$billingVillageName = \$getVal(['billing_village', 'billing_village_name'], \$village->village_name);
                    \$billingVillage = Village::where('village_name', \$billingVillageName)->first() ?? \$village;
                    
                    \$billingAddress = PartyAddress::firstOrCreate(
                        [
                            'party_id' => \$customer->id,
                            'village_id' => \$billingVillage->id,
                            'address_line_1' => \$billingAdd1,
                        ],
                        [
                            'label' => 'Imported Billing',
                            'address_line_2' => \$billingAdd2,
                            'village_name' => \$billingVillage->village_name,
                            'post_office' => \$billingPO,
                            'taluka' => \$billingTaluka,
                            'district' => \$billingVillage->district_name,
                            'city' => \$billingCity,
                            'state' => \$billingVillage->state_name,
                            'pincode' => \$billingPincode,
                        ]
                    );
                }";

$content = str_replace($target, $replacement, $content);
$content = str_replace("'shipping_address_id' => \$address->id,", "'shipping_address_id' => \$shippingAddress->id,", $content);
$content = str_replace("'billing_address_id' => \$address->id,", "'billing_address_id' => \$billingAddress->id,", $content);

file_put_contents($file, $content);
echo "Patched addresses\n";
