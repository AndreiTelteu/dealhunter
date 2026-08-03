<?php

namespace Tests\Feature;

use App\Services\AiService;
use App\Services\Crawlers\ParsedListing;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    public function test_openai_provider_uses_configured_compatible_base_url(): void
    {
        config()->set([
            'features.ai_classification_enabled' => true,
            'cache.default' => 'array',
            'ai.provider' => 'openai',
            'ai.model' => 'compatible-model',
            'ai.api_key' => 'test-key',
            'ai.openai_base_url' => 'https://compatible.example/v1/',
        ]);

        Http::fake([
            'https://compatible.example/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'matches' => true,
                            'confidence' => 0.91,
                            'reasoning' => 'Compatible provider response',
                        ], JSON_THROW_ON_ERROR),
                    ],
                ]],
            ]),
        ]);

        $result = (new AiService)->classifyIntent('laptop', new ParsedListing(
            externalId: 'compatible-base-url-test',
            url: 'https://example.test/listing',
            title: 'Laptop test compatible provider',
            description: 'Functional',
        ));

        $this->assertTrue($result['matches']);
        $this->assertSame(0.91, $result['confidence']);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://compatible.example/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'compatible-model';
        });
    }
}
