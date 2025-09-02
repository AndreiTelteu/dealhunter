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
        'intent_matching' => 'Analyze if this Romanian marketplace listing matches the search intent.

Search term: "{search_term}"
Title: "{title}"
Description: "{description}"

Consider:
- Does the title/description contain the search term or strong synonyms?
- Is this the type of item the user is looking for?
- Are there any obvious mismatches?

Respond with JSON:
{
  "matches": boolean,
  "confidence": float (0.0-1.0),
  "reasoning": "Brief explanation of your decision"
}',

        'working_condition' => 'Analyze if this item is likely in working condition based on the Romanian listing.

Title: "{title}"
Description: "{description}"

Look for Romanian keywords indicating:
- Broken/defective: stricat, defect, pentru piese, nu funcționează, spart, deteriorat
- Working: funcțional, merge perfect, stare bună, ca nou, testat
- Uncertain: nu știu, posibil, cred că, nu garantez

Respond with JSON:
{
  "working": boolean or null (null for uncertain),
  "confidence": float (0.0-1.0),
  "reasoning": "Brief explanation focusing on key indicators found"
}',
    ],

];