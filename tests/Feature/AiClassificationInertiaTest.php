<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AiClassificationInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    private function createUser(string $email = 'ai-classification@example.test'): User
    {
        return User::create([
            'name' => 'AI Classification User',
            'email' => $email,
            'password' => 'secret-password',
            'email_verified_at' => now(),
        ]);
    }

    private function disableAi(): void
    {
        config()->set([
            'features.ai_classification_enabled' => false,
            'ai.provider' => 'openai',
            'ai.model' => 'gpt-3.5-turbo',
            'ai.api_key' => '',
        ]);
    }

    private function enableAi(string $baseUrl = 'https://fake-ai.test/v1'): void
    {
        config()->set([
            'features.ai_classification_enabled' => true,
            'cache.default' => 'array',
            'ai.provider' => 'openai',
            'ai.model' => 'gpt-4',
            'ai.api_key' => 'super-secret-test-key',
            'ai.openai_base_url' => $baseUrl,
        ]);
    }

    public function test_guests_cannot_access_the_ai_classification_page(): void
    {
        $this->get('/ai-classification')->assertRedirect('/login');
    }

    public function test_ai_classification_renders_component_with_props_when_disabled(): void
    {
        $this->disableAi();
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('ai-classification.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AiClassification/Index')
                ->where('aiEnabled', false)
                ->where('currentProvider', 'openai')
                ->where('currentModel', 'gpt-3.5-turbo')
                ->where('connectionTest.success', false)
                ->has('availableModels')
                ->has('links.test')
                ->has('links.testConnection'));
    }

    public function test_ai_classification_reports_connection_state_and_never_leaks_secrets(): void
    {
        $this->enableAi();
        Http::fake([
            'https://fake-ai.test/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => '{"test": true, "message": "Connection successful"}'],
                ]],
            ]),
        ]);
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('ai-classification.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AiClassification/Index')
                ->where('aiEnabled', true)
                ->where('currentModel', 'gpt-4')
                ->where('connectionTest.success', true)
                ->where('connectionTest.provider', 'openai')
                ->where('connectionTest.model', 'gpt-4'))
            ->assertDontSee('super-secret-test-key');
    }

    public function test_test_endpoint_validates_input(): void
    {
        $this->disableAi();
        $user = $this->createUser();

        $this->actingAs($user)
            ->postJson(route('ai-classification.test'), [
                'search_term' => '',
                'title' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['search_term', 'title']);
    }

    public function test_test_endpoint_returns_classification_comparison(): void
    {
        $this->enableAi();
        Http::fake(function (Request $request) {
            $prompt = $request['messages'][1]['content'] ?? '';

            if (str_contains($prompt, 'actual product')) {
                return Http::response(['choices' => [[
                    'message' => ['content' => json_encode([
                        'intent_score' => 91,
                        'is_target_product' => true,
                        'reasoning' => 'It is a laptop',
                    ], JSON_THROW_ON_ERROR)],
                ]]]);
            }

            return Http::response(['choices' => [[
                'message' => ['content' => json_encode([
                    'working' => true,
                    'confidence' => 0.9,
                    'reasoning' => 'Functional',
                ], JSON_THROW_ON_ERROR)],
            ]]]);
        });
        $user = $this->createUser();

        $this->actingAs($user)
            ->postJson(route('ai-classification.test'), [
                'search_term' => 'laptop',
                'title' => 'Laptop Dell Latitude E7450',
                'description' => 'Functional',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'ai_result' => [
                    'intent_score' => 91,
                    'matches_intent' => true,
                    'likely_working' => true,
                ],
                'keyword_result' => [
                    'matches_intent' => true,
                ],
                'comparison' => [
                    'intent_match' => true,
                    'working_condition_match' => true,
                ],
            ]);
    }

    public function test_test_connection_endpoint_reflects_disabled_ai(): void
    {
        $this->disableAi();
        $user = $this->createUser();

        $this->actingAs($user)
            ->postJson(route('ai-classification.test-connection'))
            ->assertOk()
            ->assertJson([
                'success' => false,
                'result' => [
                    'success' => false,
                ],
            ]);
    }
}
