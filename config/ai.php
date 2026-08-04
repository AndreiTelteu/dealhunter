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
    // Listings with an intent score below this value are hidden by default.
    'intent_score_threshold' => env('AI_INTENT_SCORE_THRESHOLD', 60),
    'provider' => env('AI_PROVIDER', 'openai'),
    'model' => env('AI_MODEL', 'gpt-3.5-turbo'),
    'api_key' => env('AI_API_KEY'),
    // OpenAI-compatible API root. Include any provider-specific version segment,
    // for example https://api.openai.com/v1 or https://gateway.example/v1.
    'openai_base_url' => rtrim((string) env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),

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
        'intent_matching' => 'Analyze if this Romanian marketplace listing is the actual product the user wants to buy.

Search term: "{search_term}"
Title: "{title}"
Description: "{description}"

CRITICAL: The user wants to BUY the product itself, not accessories, parts, cables, adapters, cases, or other items that merely mention the product name.

Scoring guide (0-100):
- 90-100: The listing IS the exact product (e.g. search "placa video" and listing sells an actual graphics card)
- 70-89: The product itself but with minor differences (different model variant, bundle including the product)
- 40-69: Related but not the target product (accessory, cable, adapter, part FOR the product, case/stand)
- 10-39: Only tangentially related (mentions the product name but sells something else entirely)
- 0-9: Completely unrelated

Examples of LOW scores:
- Search "placa video" -> "Cablu alimentare placa video" = score 15 (it is a cable, not a GPU)
- Search "iphone 15" -> "Husa pentru iphone 15" = score 10 (it is a case, not a phone)
- Search "laptop dell" -> "Incarcator laptop dell" = score 15 (it is a charger, not a laptop)

Examples of HIGH scores:
- Search "placa video" -> "RTX 4070 placa video, stare perfecta" = score 95
- Search "iphone 15" -> "iPhone 15 Pro 128GB, functional" = score 95

Respond with ONLY valid JSON (no markdown, no explanation outside JSON):
{
  "intent_score": integer (0-100),
  "is_target_product": boolean (true if score >= 70),
  "reasoning": "Brief explanation in English of why this score was given"
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
