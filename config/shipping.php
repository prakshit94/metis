<?php

return [
    'default' => env('SHIPPING_PROVIDER', 'india_post'),

    'providers' => [
        'india_post' => [
            'base_url' => env('INDIA_POST_BASE_URL', env('APP_ENV') === 'production'
                ? 'https://app.indiapost.gov.in/beextcustomer'
                : 'https://test.cept.gov.in/beextcustomer'),
            'username' => env('INDIA_POST_USERNAME', env('APP_ENV') === 'production' ? '' : '9999999999'),
            'password' => env('INDIA_POST_PASSWORD', env('APP_ENV') === 'production' ? '' : 'Dop@1234'),
            'bulk_customer_id' => env('INDIA_POST_BULK_CUSTOMER_ID', env('APP_ENV') === 'production' ? '' : '3000064781'),
            'pickup_dropoff_office_id' => env('INDIA_POST_PICKUP_OFFICE_ID', env('APP_ENV') === 'production' ? '' : '21260024'),
            'drop_off_pincode' => env('INDIA_POST_DROP_OFF_PINCODE', env('APP_ENV') === 'production' ? '' : '600001'),
            'booking_office_name' => env('INDIA_POST_BOOKING_OFFICE_NAME', env('APP_ENV') === 'production' ? '' : 'Default Booking Office'),
            'booking_office_pin' => env('INDIA_POST_BOOKING_OFFICE_PIN', env('APP_ENV') === 'production' ? '' : '600001'),
            'barcode' => [
                'prefix' => env('INDIA_POST_BARCODE_PREFIX', env('APP_ENV') === 'production' ? null : 'EA'),
                'start' => env('INDIA_POST_BARCODE_START', 10000000),
                'end' => env('INDIA_POST_BARCODE_END', 19999999),
            ],

            // Default contract IDs for different services
            'contracts' => [
                'SP_INLAND_DOC' => env('INDIA_POST_CONTRACT_SP', env('APP_ENV') === 'production' ? '' : '41585456'),
                'SP_INLAND_PARCEL' => env('INDIA_POST_CONTRACT_SP', env('APP_ENV') === 'production' ? '' : '41585456'),
                'BUSINESS_PARCEL' => env('INDIA_POST_CONTRACT_BP', env('APP_ENV') === 'production' ? '' : '41367422'),
                '24_SPEEDPOST_DOC' => env('INDIA_POST_CONTRACT_24_SP_DOC', env('APP_ENV') === 'production' ? '' : '41469430'),
                '24_SPP_PARSPL' => env('INDIA_POST_CONTRACT_24_SP_PARSPL', env('APP_ENV') === 'production' ? '' : '41918281'),
                '48_SPEEDPOST_DOC' => env('INDIA_POST_CONTRACT_48_SP_DOC', env('APP_ENV') === 'production' ? '' : '41471113'),
            ],
        ],
    ],
];
