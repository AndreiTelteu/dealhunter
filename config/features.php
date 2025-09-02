<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Configuration to enable/disable specific features
    |
    */

    'ai_classification_enabled' => env('AI_CLASSIFICATION_ENABLED', true),
    'detail_page_crawling' => env('DETAIL_PAGE_CRAWLING', false),
    'image_url_extraction' => env('IMAGE_URL_EXTRACTION', true),
    'seller_info_extraction' => env('SELLER_INFO_EXTRACTION', true),

];