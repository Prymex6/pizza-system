<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Przelewy24 Configuration
    |--------------------------------------------------------------------------
    |
    | Konfiguracja dla płatności Przelewy24
    | Sandbox: https://sandbox.przelewy24.pl
    | Production: https://secure.przelewy24.pl
    |
    | NOTE: Each restaurant (tenant) configures their own Przelewy24 credentials
    | via tenant settings in the database. This config file only contains
    | global settings that are the same for all tenants.
    |
    */

    'urls' => [
        'sandbox' => 'https://sandbox.przelewy24.pl',
        'production' => 'https://secure.przelewy24.pl',
    ],

    // Waluty (domyślna dla wszystkich restauracji)
    'currency' => 'PLN',

    // Język interfejsu P24 (domyślny dla wszystkich restauracji)
    'language' => 'pl',

    /*
    |--------------------------------------------------------------------------
    | Platform P24 Credentials (for Subscription Payments)
    |--------------------------------------------------------------------------
    |
    | These are YOUR Przelewy24 credentials for receiving subscription payments
    | FROM restaurants. Restaurants pay YOU for the SaaS subscription.
    |
    */
    'platform_merchant_id' => env('P24_PLATFORM_MERCHANT_ID'),
    'platform_pos_id' => env('P24_PLATFORM_POS_ID'),
    'platform_api_key' => env('P24_PLATFORM_API_KEY'),
    'platform_crc' => env('P24_PLATFORM_CRC'),
    'platform_mode' => env('P24_PLATFORM_MODE', 'sandbox'),
];
