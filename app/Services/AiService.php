<?php

namespace App\Services;

use App\Services\Crawlers\ParsedListing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * AI Service for classification and analysis
 *
 * Provides integration with various AI providers for intent matching
 * and working condition assessment
 */
class AiService extends BaseService
{
    protected string $logChannel = 'ai';

    private string $provider;

    private string $model;

    private string $apiKey;

    private string $openaiBaseUrl;

    private float $confidenceThreshold;

    public function __construct()
    {
        parent::__construct();

        $this->provider = config('ai.provider', 'openai');
        $this->model = config('ai.model', 'gpt-3.5-turbo');
        $this->apiKey = config('ai.api_key');
        $this->openaiBaseUrl = rtrim((string) config('ai.openai_base_url', 'https://api.openai.com/v1'), '/');
        $this->confidenceThreshold = config('ai.confidence_threshold', 0.7);
    }

    /**
     * Classify intent matching for a listing
     *
     * @return array ['matches' => bool, 'confidence' => float, 'reasoning' => string]
     */
    public function classifyIntent(string $searchTerm, ParsedListing $listing): array
    {
        if (! $this->isEnabled()) {
            return [
                'matches' => false,
                'confidence' => 0.0,
                'reasoning' => 'AI classification disabled or API key missing',
            ];
        }

        return $this->executeWithErrorHandling(
            function () use ($searchTerm, $listing) {
                $cacheKey = $this->getCacheKey('intent', $searchTerm, $listing->title, $listing->description);

                return Cache::remember($cacheKey, 3600, function () use ($searchTerm, $listing) {
                    $prompt = $this->buildIntentPrompt($searchTerm, $listing);
                    $response = $this->callAiProvider($prompt);

                    return $this->parseIntentResponse($response);
                });
            },
            [
                'search_term' => $searchTerm,
                'title' => $listing->title,
                'provider' => $this->provider,
                'model' => $this->model,
            ],
            'classify_intent'
        );
    }

    /**
     * Assess working condition of an item
     *
     * @return array ['working' => bool|null, 'confidence' => float, 'reasoning' => string]
     */
    public function assessWorkingCondition(ParsedListing $listing): array
    {
        if (! $this->isEnabled()) {
            return [
                'working' => null,
                'confidence' => 0.0,
                'reasoning' => 'AI classification disabled or API key missing',
            ];
        }

        return $this->executeWithErrorHandling(
            function () use ($listing) {
                $cacheKey = $this->getCacheKey('working', $listing->description ?? '');

                return Cache::remember($cacheKey, 3600, function () use ($listing) {
                    $prompt = $this->buildWorkingConditionPrompt($listing);
                    $response = $this->callAiProvider($prompt);

                    return $this->parseWorkingConditionResponse($response);
                });
            },
            [
                'title' => $listing->title,
                'description_length' => strlen($listing->description ?? ''),
                'provider' => $this->provider,
                'model' => $this->model,
            ],
            'assess_working_condition'
        );
    }

    /**
     * Perform comprehensive classification
     */
    public function comprehensiveClassification(string $searchTerm, ParsedListing $listing): array
    {
        $intentResult = $this->classifyIntent($searchTerm, $listing);
        $workingResult = $this->assessWorkingCondition($listing);

        // Calculate overall confidence based on both classifications
        $overallConfidence = $this->calculateOverallConfidence($intentResult, $workingResult);

        return [
            'matches_intent' => $intentResult['matches'],
            'likely_working' => $workingResult['working'],
            'confidence' => $overallConfidence,
            'intent_confidence' => $intentResult['confidence'],
            'working_confidence' => $workingResult['confidence'],
            'reasoning' => $this->combineReasoning($intentResult['reasoning'], $workingResult['reasoning']),
        ];
    }

    /**
     * Build prompt for intent classification
     */
    private function buildIntentPrompt(string $searchTerm, ParsedListing $listing): string
    {
        $template = config('ai.prompts.intent_matching',
            'Analyze if this listing matches the search intent for "{search_term}". Title: "{title}". Description: "{description}". Return JSON with "matches" (boolean), "confidence" (0-1), and "reasoning" (string).'
        );

        return str_replace([
            '{search_term}',
            '{title}',
            '{description}',
        ], [
            $searchTerm,
            $listing->title,
            $listing->description ?? '',
        ], $template);
    }

    /**
     * Build prompt for working condition assessment
     */
    private function buildWorkingConditionPrompt(ParsedListing $listing): string
    {
        $template = config('ai.prompts.working_condition',
            'Analyze if this item is likely working based on the Romanian description: "{description}". Look for keywords indicating broken/defective items. Return JSON with "working" (boolean or null), "confidence" (0-1), and "reasoning" (string).'
        );

        $brokenKeywords = implode(', ', config('ai.broken_keywords', []));

        $prompt = str_replace([
            '{title}',
            '{description}',
            '{broken_keywords}',
        ], [
            $listing->title,
            $listing->description ?? '',
            $brokenKeywords,
        ], $template);

        return $prompt."\n\nCommon Romanian keywords for broken items: ".$brokenKeywords;
    }

    /**
     * Call the configured AI provider
     */
    private function callAiProvider(string $prompt): string
    {
        switch ($this->provider) {
            case 'openai':
                return $this->callOpenAI($prompt);
            case 'anthropic':
                return $this->callAnthropic($prompt);
            default:
                throw new ServiceException("Unsupported AI provider: {$this->provider}");
        }
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI(string $prompt): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($this->openaiBaseUrl.'/chat/completions', [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert at analyzing Romanian marketplace listings. Always respond with valid JSON.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.1,
            'max_tokens' => 500,
        ]);

        if (! $response->successful()) {
            throw new ServiceException(
                'OpenAI API request failed',
                context: [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]
            );
        }

        $data = $response->json();

        if (! isset($data['choices'][0]['message']['content'])) {
            throw new ServiceException(
                'Invalid OpenAI response format',
                context: ['response' => $data]
            );
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Call Anthropic API
     */
    private function callAnthropic(string $prompt): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 500,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new ServiceException(
                'Anthropic API request failed',
                context: [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]
            );
        }

        $data = $response->json();

        if (! isset($data['content'][0]['text'])) {
            throw new ServiceException(
                'Invalid Anthropic response format',
                context: ['response' => $data]
            );
        }

        return $data['content'][0]['text'];
    }

    /**
     * Parse intent classification response
     */
    private function parseIntentResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return [
                'matches' => (bool) ($data['matches'] ?? false),
                'confidence' => (float) ($data['confidence'] ?? 0.0),
                'reasoning' => (string) ($data['reasoning'] ?? 'No reasoning provided'),
            ];
        } catch (\JsonException $e) {
            $this->logWarning('Failed to parse AI response as JSON', [
                'response' => $response,
                'error' => $e->getMessage(),
            ]);

            // Fallback: try to extract boolean from text
            $matches = str_contains(strtolower($response), 'true') ||
                      str_contains(strtolower($response), 'yes') ||
                      str_contains(strtolower($response), 'match');

            return [
                'matches' => $matches,
                'confidence' => 0.5,
                'reasoning' => 'Parsed from non-JSON response: '.substr($response, 0, 100),
            ];
        }
    }

    /**
     * Parse working condition response
     */
    private function parseWorkingConditionResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            $working = $data['working'] ?? null;
            if ($working === 'true' || $working === true) {
                $working = true;
            } elseif ($working === 'false' || $working === false) {
                $working = false;
            } else {
                $working = null;
            }

            return [
                'working' => $working,
                'confidence' => (float) ($data['confidence'] ?? 0.0),
                'reasoning' => (string) ($data['reasoning'] ?? 'No reasoning provided'),
            ];
        } catch (\JsonException $e) {
            $this->logWarning('Failed to parse AI response as JSON', [
                'response' => $response,
                'error' => $e->getMessage(),
            ]);

            // Fallback: try to extract working condition from text
            $responseLower = strtolower($response);
            if (str_contains($responseLower, 'working') || str_contains($responseLower, 'functional')) {
                $working = true;
            } elseif (str_contains($responseLower, 'broken') || str_contains($responseLower, 'defective')) {
                $working = false;
            } else {
                $working = null;
            }

            return [
                'working' => $working,
                'confidence' => 0.5,
                'reasoning' => 'Parsed from non-JSON response: '.substr($response, 0, 100),
            ];
        }
    }

    /**
     * Calculate overall confidence from multiple classifications
     */
    private function calculateOverallConfidence(array $intentResult, array $workingResult): float
    {
        // Weight intent classification higher than working condition
        $intentWeight = 0.6;
        $workingWeight = 0.4;

        $overallConfidence = ($intentResult['confidence'] * $intentWeight) +
                           ($workingResult['confidence'] * $workingWeight);

        return min(1.0, max(0.0, $overallConfidence));
    }

    /**
     * Combine reasoning from multiple classifications
     */
    private function combineReasoning(string $intentReasoning, string $workingReasoning): string
    {
        return "Intent: {$intentReasoning}; Working condition: {$workingReasoning}";
    }

    /**
     * Generate cache key for AI responses
     */
    private function getCacheKey(string $type, string ...$inputs): string
    {
        $hash = md5(implode('|', $inputs));

        return "ai_classification:{$type}:{$this->provider}:{$this->model}:{$hash}";
    }

    /**
     * Check if AI classification is enabled
     */
    private function isEnabled(): bool
    {
        return config('features.ai_classification_enabled', true) &&
               ! empty($this->apiKey);
    }

    /**
     * Get provider-specific model options
     */
    public function getAvailableModels(): array
    {
        return match ($this->provider) {
            'openai' => [
                'gpt-3.5-turbo',
                'gpt-3.5-turbo-16k',
                'gpt-4',
                'gpt-4-turbo-preview',
            ],
            'anthropic' => [
                'claude-3-haiku-20240307',
                'claude-3-sonnet-20240229',
                'claude-3-opus-20240229',
            ],
            default => []
        };
    }

    /**
     * Test AI provider connection
     */
    public function testConnection(): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'AI classification is disabled or API key is missing',
            ];
        }

        try {
            $testPrompt = 'Respond with JSON: {"test": true, "message": "Connection successful"}';
            $response = $this->callAiProvider($testPrompt);

            return [
                'success' => true,
                'provider' => $this->provider,
                'model' => $this->model,
                'response' => $response,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $this->provider,
                'model' => $this->model,
            ];
        }
    }
}
