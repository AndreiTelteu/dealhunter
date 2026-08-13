<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use App\Services\Crawlers\ParsedListing;
use App\Services\IntentClassifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiClassificationController extends Controller
{
    public function __construct(
        private AiService $aiService,
        private IntentClassifierService $classifier
    ) {}

    /**
     * Show AI classification testing interface
     */
    public function index(): Response
    {
        $availableModels = $this->aiService->getAvailableModels();
        $connectionTest = $this->aiService->testConnection();

        return Inertia::render('AiClassification/Index', [
            'availableModels' => $availableModels,
            'connectionTest' => $connectionTest,
            'currentProvider' => config('ai.provider'),
            'currentModel' => config('ai.model'),
            'aiEnabled' => (bool) config('features.ai_classification_enabled'),
            'links' => [
                'test' => route('ai-classification.test'),
                'testConnection' => route('ai-classification.test-connection'),
            ],
        ]);
    }

    /**
     * Test AI classification via AJAX
     */
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'search_term' => 'required|string|max:255',
            'title' => 'required|string|max:500',
            'description' => 'nullable|string|max:2000',
        ]);

        try {
            $listing = new ParsedListing(
                externalId: 'test-'.time(),
                url: 'https://example.com/test',
                title: $request->input('title'),
                description: $request->input('description', ''),
            );

            $searchTerm = $request->input('search_term');

            // Get AI classification
            $aiResult = $this->aiService->comprehensiveClassification($searchTerm, $listing);

            // Get keyword-based classification for comparison
            $keywordResult = $this->classifier->classifyListing($searchTerm, $listing);

            return response()->json([
                'success' => true,
                'ai_result' => $aiResult,
                'keyword_result' => $keywordResult->toArray(),
                'comparison' => [
                    'intent_match' => $aiResult['matches_intent'] === $keywordResult->matchesIntent,
                    'working_condition_match' => $aiResult['likely_working'] === $keywordResult->likelyWorking,
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Test AI provider connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $result = $this->aiService->testConnection();

            return response()->json([
                'success' => $result['success'],
                'result' => $result,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
