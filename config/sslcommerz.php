<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gateway mode
    |--------------------------------------------------------------------------
    | "demo"    -> redirects to the in-app demo gateway (/payment/demo/{order})
    | "sandbox" -> creates a real SSLCommerz sandbox session using credentials
    */
    'mode' => env('SSLCOMMERZ_MODE', 'demo'),

    'sandbox' => (bool) env('SSLCOMMERZ_SANDBOX', true),

    'store_id' => env('SSLCOMMERZ_STORE_ID'),

    'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),

    'currency' => env('SSLCOMMERZ_CURRENCY', 'BDT'),

    'endpoints' => [
        'sandbox' => [
            'init' => 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php',
            'validate' => 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php',
        ],
        'live' => [
            'init' => 'https://securepay.sslcommerz.com/gwprocess/v4/api.php',
            'validate' => 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User facing labels
    |--------------------------------------------------------------------------
    */
    'label' => 'SSLCommerz (Demo)',

];
