<?php

return [
    'default' => env('SHIPPING_PROVIDER', 'india_post'),

    'providers' => [
        'india_post' => [
            'base_url' => env('INDIA_POST_BASE_URL', 'https://test.cept.gov.in/beextcustomer'),
            'username' => env('INDIA_POST_USERNAME', '9999999999'),
            'password' => env('INDIA_POST_PASSWORD', 'Dop@1234'),
            'bulk_customer_id' => env('INDIA_POST_BULK_CUSTOMER_ID', '3000064781'),

            // Default contract IDs for different services
            'contracts' => [
                'SP_INLAND_DOC' => env('INDIA_POST_CONTRACT_SP', '41585456'), // Speed Post
                'SP_INLAND_PARCEL' => env('INDIA_POST_CONTRACT_SP', '41585456'), // Speed Post
                'BUSINESS_PARCEL' => env('INDIA_POST_CONTRACT_BP', '41367422'),
                '24_SPEEDPOST_DOC' => env('INDIA_POST_CONTRACT_24_SP_DOC', '41469430'),
                '24_SPP_PARSPL' => env('INDIA_POST_CONTRACT_24_SP_PARSPL', '41918281'),
                '48_SPEEDPOST_DOC' => env('INDIA_POST_CONTRACT_48_SP_DOC', '41471113'),
            ],
        ],
    ],
];
