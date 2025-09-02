<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for the application (Romanian Leu)
    |
    */

    'default' => env('DEFAULT_CURRENCY', 'RON'),

    /*
    |--------------------------------------------------------------------------
    | Currency Conversion Rates
    |--------------------------------------------------------------------------
    |
    | Exchange rates for converting various currencies to RON
    | These should be updated regularly or fetched from an API
    |
    */

    'rates' => [
        'RON' => 1.0,
        'EUR' => env('EUR_TO_RON_RATE', 4.95),
        'USD' => env('USD_TO_RON_RATE', 4.50),
        'LEI' => 1.0, // Alternative Romanian currency notation
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Symbols
    |--------------------------------------------------------------------------
    |
    | Mapping of currency symbols to currency codes
    |
    */

    'symbols' => [
        '€' => 'EUR',
        '$' => 'USD',
        'lei' => 'RON',
        'ron' => 'RON',
        'eur' => 'EUR',
        'usd' => 'USD',
        'euro' => 'EUR',
        'dolari' => 'USD',
        'dolari americani' => 'USD',
    ],

];