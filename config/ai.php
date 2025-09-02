<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Classification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI-powered intent matching and working condition
    | assessment of listings
    |
    */

    'confidence_threshold' => env('AI_CONFIDENCE_THRESHOLD', 0.7),
    'provider' => env('AI_PROVIDER', 'openai'),
    'model' => env('AI_MODEL', 'gpt-3.5-turbo'),
    'api_key' => env('AI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Romanian Keywords for Broken Items
    |--------------------------------------------------------------------------
    |
    | Keywords that indicate items are broken or not working
    |
    */

    'broken_keywords' => [
        'stricat',
        'defect',
        'pentru piese',
        'nu funcționează',
        'nu merge',
        'spart',
        'deteriorat',
        'avariat',
        'nu pornește',
        'defecțiune',
        'nefuncțional',
        'pentru dezmembrare',
    ],

    /*
    |--------------------------------------------------------------------------
    | Classification Prompts
    |--------------------------------------------------------------------------
    |
    | Templates for AI classification prompts
    |
    */

    'prompts' => [
        'intent_matching' => 'Analyze if this listing matches the search intent for "{search_term}". Title: "{title}". Description: "{description}". Return true/false and confidence 0-1.',
        'working_condition' => 'Analyze if this item is likely working based on the Romanian description: "{description}". Look for keywords indicating broken/defective items. Return true/false and confidence 0-1.',
    ],

];