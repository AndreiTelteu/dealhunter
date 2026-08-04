<?php

namespace App\Console\Commands;

use App\Services\AiService;
use App\Services\Crawlers\ParsedListing;
use App\Services\IntentClassifierService;
use Illuminate\Console\Command;

class TestAiClassificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test-classification 
                            {--search-term=laptop : Search term to test}
                            {--title=Laptop Dell Latitude : Listing title}
                            {--description=Laptop functional, stare buna : Listing description}
                            {--test-connection : Test AI provider connection only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AI classification functionality';

    /**
     * Execute the console command.
     */
    public function handle(AiService $aiService, IntentClassifierService $classifier): int
    {
        if ($this->option('test-connection')) {
            return $this->testConnection($aiService);
        }

        return $this->testClassification($aiService, $classifier);
    }

    /**
     * Test AI provider connection
     */
    private function testConnection(AiService $aiService): int
    {
        $this->info('Testing AI provider connection...');

        $result = $aiService->testConnection();

        if ($result['success']) {
            $this->info('✅ Connection successful!');
            $this->line("Provider: {$result['provider']}");
            $this->line("Model: {$result['model']}");
            $this->line("Response: {$result['response']}");

            return 0;
        } else {
            $this->error('❌ Connection failed!');
            $this->error("Error: {$result['error']}");
            if (isset($result['provider'])) {
                $this->line("Provider: {$result['provider']}");
                $this->line("Model: {$result['model']}");
            }

            return 1;
        }
    }

    /**
     * Test classification functionality
     */
    private function testClassification(AiService $aiService, IntentClassifierService $classifier): int
    {
        $searchTerm = $this->option('search-term');
        $title = $this->option('title');
        $description = $this->option('description');

        $this->info('Testing AI Classification');
        $this->line("Search term: {$searchTerm}");
        $this->line("Title: {$title}");
        $this->line("Description: {$description}");
        $this->newLine();

        // Create test listing
        $listing = new ParsedListing(
            externalId: 'test-123',
            url: 'https://example.com/test',
            title: $title,
            description: $description
        );

        try {
            // Test direct AI service
            $this->info('🤖 Testing AI Service directly...');

            $intentResult = $aiService->classifyIntent($searchTerm, $listing);
            $this->line('Intent Match: '.($intentResult['matches'] ? '✅ Yes' : '❌ No'));
            $this->line("Intent Score: {$intentResult['intent_score']}/100");
            $this->line("Reasoning: {$intentResult['reasoning']}");
            $this->newLine();

            $workingResult = $aiService->assessWorkingCondition($listing);
            $workingStatus = match ($workingResult['working']) {
                true => '✅ Working',
                false => '❌ Broken',
                null => '❓ Uncertain'
            };
            $this->line("Working Condition: {$workingStatus}");
            $this->line("Confidence: {$workingResult['confidence']}");
            $this->line("Reasoning: {$workingResult['reasoning']}");
            $this->newLine();

            // Test comprehensive classification
            $this->info('🔍 Testing Comprehensive Classification...');
            $comprehensiveResult = $aiService->comprehensiveClassification($searchTerm, $listing);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Intent Score', $comprehensiveResult['intent_score'].'/100'],
                    ['Matches Intent', $comprehensiveResult['matches_intent'] ? 'Yes' : 'No'],
                    ['Likely Working', match ($comprehensiveResult['likely_working']) {
                        true => 'Yes',
                        false => 'No',
                        null => 'Uncertain'
                    }],
                    ['Overall Confidence', number_format($comprehensiveResult['confidence'], 2)],
                    ['Intent Confidence', number_format($comprehensiveResult['intent_confidence'], 2)],
                    ['Working Confidence', number_format($comprehensiveResult['working_confidence'], 2)],
                ]
            );

            $this->line("Reasoning: {$comprehensiveResult['reasoning']}");
            $this->newLine();

            // Test classifier service (with keyword fallback)
            $this->info('🎯 Testing Intent Classifier Service...');
            $classification = $classifier->classifyListing($searchTerm, $listing);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Intent Score', ($classification->intentScore ?? '-').'/100'],
                    ['Matches Intent', $classification->matchesIntent ? 'Yes' : 'No'],
                    ['Likely Working', $classification->getWorkingConditionString()],
                    ['Confidence', number_format($classification->confidence, 2)],
                    ['High Confidence', $classification->isHighConfidence() ? 'Yes' : 'No'],
                ]
            );

            $this->line("Reasoning: {$classification->reasoning}");

            $this->newLine();
            $this->info('✅ All tests completed successfully!');

            return 0;

        } catch (\Throwable $e) {
            $this->error('❌ Test failed!');
            $this->error("Error: {$e->getMessage()}");
            $this->line("File: {$e->getFile()}:{$e->getLine()}");

            if ($this->output->isVerbose()) {
                $this->line('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }
}
