<?php

namespace Tests\Feature;

use App\Services\Crawlers\ParsedListing;
use App\Services\IntentClassifierService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntentClassifierKeywordTest extends TestCase
{
    private IntentClassifierService $classifier;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('features.ai_classification_enabled', false);
        $this->classifier = app(IntentClassifierService::class);
    }

    private function listing(string $title, string $description = ''): ParsedListing
    {
        return new ParsedListing(
            externalId: 'test-'.uniqid(),
            url: 'https://example.com/test',
            title: $title,
            description: $description,
        );
    }

    public function test_exact_product_title_scores_high(): void
    {
        $classification = $this->classifier->classifyListing(
            'placa video',
            $this->listing('Placa video RTX 4070 Super', 'Stare perfecta, testata.')
        );

        $this->assertTrue($classification->matchesIntent);
        $this->assertSame(100, $classification->intentScore);
    }

    public function test_accessory_listing_scores_low(): void
    {
        $classification = $this->classifier->classifyListing(
            'placa video',
            $this->listing('Cablu alimentare placa video', 'Cablu nou, compatibil.')
        );

        $this->assertFalse($classification->matchesIntent);
        $this->assertSame(30, $classification->intentScore);
    }

    public function test_case_listing_scores_low(): void
    {
        $classification = $this->classifier->classifyListing(
            'iphone 15',
            $this->listing('Husa pentru iPhone 15 Pro', 'Silicon, culoare neagra.')
        );

        $this->assertFalse($classification->matchesIntent);
    }

    public function test_synonym_match_scores_medium(): void
    {
        $classification = $this->classifier->classifyListing(
            'laptop',
            $this->listing('Notebook Dell Latitude 5420', 'Functional, 16GB RAM.')
        );

        $this->assertSame(80, $classification->intentScore);
        $this->assertTrue($classification->matchesIntent);
    }

    public function test_unrelated_listing_scores_zero(): void
    {
        $classification = $this->classifier->classifyListing(
            'placa video',
            $this->listing('Canapea extensibila 3 locuri', 'Stare buna.')
        );

        $this->assertFalse($classification->matchesIntent);
        $this->assertSame(0, $classification->intentScore);
    }

    public function test_ai_fallback_used_when_ai_enabled_but_fails(): void
    {
        config()->set([
            'features.ai_classification_enabled' => true,
            'ai.api_key' => 'test-key',
            'cache.default' => 'array',
        ]);
        $this->classifier = app(IntentClassifierService::class);

        Http::fake(fn () => Http::response(['error' => 'unavailable'], 500));

        $classification = $this->classifier->classifyListing(
            'placa video',
            $this->listing('Cablu alimentare placa video')
        );

        $this->assertFalse($classification->matchesIntent);
        $this->assertSame(30, $classification->intentScore);
    }

    public function test_ai_score_takes_precedence_over_keywords(): void
    {
        config()->set([
            'features.ai_classification_enabled' => true,
            'ai.api_key' => 'test-key',
            'ai.openai_base_url' => 'https://api.openai.com/v1',
            'cache.default' => 'array',
        ]);
        $this->classifier = app(IntentClassifierService::class);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'intent_score' => 10,
                            'is_target_product' => false,
                            'reasoning' => 'It is a cable, not a GPU',
                        ], JSON_THROW_ON_ERROR),
                    ],
                ]],
            ], 200),
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'working' => null,
                            'confidence' => 0.5,
                            'reasoning' => 'uncertain',
                        ], JSON_THROW_ON_ERROR),
                    ],
                ]],
            ], 200),
        ]);

        $classification = $this->classifier->classifyListing(
            'placa video',
            $this->listing('Cablu alimentare placa video', 'Cablul contine placa video in titlu.')
        );

        $this->assertFalse($classification->matchesIntent);
        $this->assertSame(10, $classification->intentScore);
    }
}
