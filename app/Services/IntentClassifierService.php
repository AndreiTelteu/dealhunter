<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Service for classifying listing intent and working condition using AI and keyword analysis
 * 
 * Provides Romanian keyword detection for broken items and AI-powered intent matching
 */
class IntentClassifierService extends BaseService
{
    protected string $logChannel = 'classifier';
    
    /**
     * Romanian keywords indicating broken or non-working items
     */
    private const BROKEN_KEYWORDS = [
        'stricat', 'stricata', 'stricati', 'stricate',
        'defect', 'defecta', 'defecti', 'defecte',
        'pentru piese', 'pt piese', 'piese',
        'nu funcționează', 'nu functioneaza', 'nefunctional', 'nefunctionala',
        'spart', 'sparta', 'sparti', 'sparte',
        'rupt', 'rupta', 'rupti', 'rupte',
        'deteriorat', 'deteriorata', 'deteriorati', 'deteriorate',
        'uzat', 'uzata', 'uzati', 'uzate',
        'fără garanție', 'fara garantie',
        'nu merge', 'nu mai merge',
        'blocat', 'blocata', 'blocati', 'blocate',
        'crăpat', 'crapata', 'crapati', 'crapate',
        'zgâriat', 'zgariata', 'zgariati', 'zgariate',
        'lovit', 'lovita', 'loviti', 'lovite',
        'accident', 'accidentat', 'accidentata',
        'vandalizat', 'vandalizata',
        'inundat', 'inundata',
        'ars', 'arsa', 'arsi', 'arse',
    ];
    
    /**
     * Keywords indicating uncertainty about working condition
     */
    private const UNCERTAIN_KEYWORDS = [
        'nu știu', 'nu stiu',
        'posibil', 'poate',
        'cred că', 'cred ca',
        'pare că', 'pare ca',
        'nu sunt sigur', 'nesigur',
        'nu garantez',
        'vândut ca văzut', 'vandut ca vazut',
        'fără returnare', 'fara returnare',
    ];
    
    /**
     * Positive working condition keywords
     */
    private const WORKING_KEYWORDS = [
        'funcțional', 'functional', 'functionala',
        'merge perfect', 'merge bine',
        'în stare bună', 'in stare buna',
        'ca nou', 'ca noua',
        'testat', 'testata',
        'garanție', 'garantie',
        'perfect funcțional', 'perfect functional',
        'fără probleme', 'fara probleme',
        'impecabil', 'impecabila',
    ];
    
    /**
     * Classify a listing for intent matching and working condition
     * 
     * @param string $searchTerm
     * @param ParsedListing $listing
     * @return Classification
     */
    public function classifyListing(string $searchTerm, ParsedListing $listing): Classification
    {
        $intentMatch = $this->matchesIntent($searchTerm, $listing->title, $listing->description);
        $workingCondition = $this->assessWorkingCondition($listing->description);
        $confidence = $this->calculateConfidence([
            'intent_match' => $intentMatch,
            'working_condition' => $workingCondition,
            'title_quality' => $this->assessTitleQuality($listing->title),
            'description_quality' => $this->assessDescriptionQuality($listing->description),
        ]);
        
        $this->logDebug('Listing classified', [
            'search_term' => $searchTerm,
            'title' => $listing->title,
            'intent_match' => $intentMatch,
            'working_condition' => $workingCondition,
            'confidence' => $confidence,
        ]);
        
        return new Classification(
            matchesIntent: $intentMatch,
            likelyWorking: $workingCondition,
            confidence: $confidence,
            reasoning: $this->generateReasoning($intentMatch, $workingCondition, $searchTerm, $listing)
        );
    }
    
    /**
     * Check if listing matches search intent
     * 
     * @param string $searchTerm
     * @param string $title
     * @param string $description
     * @return bool
     */
    private function matchesIntent(string $searchTerm, string $title, string $description): bool
    {
        $searchTermLower = mb_strtolower($searchTerm, 'UTF-8');
        $titleLower = mb_strtolower($title, 'UTF-8');
        $descriptionLower = mb_strtolower($description, 'UTF-8');
        
        // Direct match in title (highest priority)
        if (str_contains($titleLower, $searchTermLower)) {
            return true;
        }
        
        // Check individual words from search term
        $searchWords = explode(' ', $searchTermLower);
        $titleWords = explode(' ', $titleLower);
        
        $matchedWords = 0;
        foreach ($searchWords as $searchWord) {
            if (strlen($searchWord) < 3) continue; // Skip short words
            
            foreach ($titleWords as $titleWord) {
                if (str_contains($titleWord, $searchWord) || str_contains($searchWord, $titleWord)) {
                    $matchedWords++;
                    break;
                }
            }
        }
        
        // Consider it a match if most significant words are found
        $significantWords = count(array_filter($searchWords, fn($w) => strlen($w) >= 3));
        if ($significantWords > 0 && $matchedWords / $significantWords >= 0.6) {
            return true;
        }
        
        // Check description for search term (lower priority)
        if (str_contains($descriptionLower, $searchTermLower)) {
            return true;
        }
        
        // Use AI for complex intent matching if enabled
        if (config('features.ai_classification_enabled', true)) {
            return $this->aiIntentMatch($searchTerm, $title, $description);
        }
        
        return false;
    }
    
    /**
     * Assess working condition based on description analysis
     * 
     * @param string $description
     * @return bool|null True if likely working, false if likely broken, null if uncertain
     */
    private function assessWorkingCondition(string $description): ?bool
    {
        $descriptionLower = mb_strtolower($description, 'UTF-8');
        
        // Check for broken keywords
        foreach (self::BROKEN_KEYWORDS as $keyword) {
            if (str_contains($descriptionLower, $keyword)) {
                return false;
            }
        }
        
        // Check for uncertain keywords
        foreach (self::UNCERTAIN_KEYWORDS as $keyword) {
            if (str_contains($descriptionLower, $keyword)) {
                return null;
            }
        }
        
        // Check for positive working keywords
        foreach (self::WORKING_KEYWORDS as $keyword) {
            if (str_contains($descriptionLower, $keyword)) {
                return true;
            }
        }
        
        // Use AI for working condition assessment if enabled
        if (config('features.ai_classification_enabled', true)) {
            return $this->aiWorkingConditionAssessment($description);
        }
        
        // Default to uncertain if no clear indicators
        return null;
    }
    
    /**
     * Calculate confidence score based on classification signals
     * 
     * @param array $signals
     * @return float
     */
    private function calculateConfidence(array $signals): float
    {
        $confidence = 0.0;
        $factors = 0;
        
        // Intent match confidence
        if ($signals['intent_match']) {
            $confidence += 0.4;
        }
        $factors++;
        
        // Working condition confidence
        if ($signals['working_condition'] !== null) {
            $confidence += 0.3;
        }
        $factors++;
        
        // Title quality (length and detail)
        $confidence += $signals['title_quality'] * 0.2;
        $factors++;
        
        // Description quality
        $confidence += $signals['description_quality'] * 0.1;
        $factors++;
        
        return min(1.0, max(0.0, $confidence));
    }
    
    /**
     * Assess title quality
     * 
     * @param string $title
     * @return float
     */
    private function assessTitleQuality(string $title): float
    {
        $length = mb_strlen($title, 'UTF-8');
        
        // Optimal title length is 20-80 characters
        if ($length < 10) return 0.2;
        if ($length < 20) return 0.5;
        if ($length <= 80) return 1.0;
        if ($length <= 120) return 0.8;
        
        return 0.6; // Very long titles might be spam
    }
    
    /**
     * Assess description quality
     * 
     * @param string $description
     * @return float
     */
    private function assessDescriptionQuality(string $description): float
    {
        $length = mb_strlen($description, 'UTF-8');
        
        // Longer descriptions generally provide more information
        if ($length < 50) return 0.3;
        if ($length < 100) return 0.6;
        if ($length <= 500) return 1.0;
        if ($length <= 1000) return 0.9;
        
        return 0.7; // Very long descriptions might be copy-paste
    }
    
    /**
     * Generate human-readable reasoning for classification
     * 
     * @param bool $intentMatch
     * @param bool|null $workingCondition
     * @param string $searchTerm
     * @param ParsedListing $listing
     * @return string
     */
    private function generateReasoning(bool $intentMatch, ?bool $workingCondition, string $searchTerm, ParsedListing $listing): string
    {
        $reasons = [];
        
        if ($intentMatch) {
            $reasons[] = "Matches search term '{$searchTerm}'";
        } else {
            $reasons[] = "Does not clearly match search term '{$searchTerm}'";
        }
        
        if ($workingCondition === true) {
            $reasons[] = "Likely in working condition";
        } elseif ($workingCondition === false) {
            $reasons[] = "Likely broken or for parts";
        } else {
            $reasons[] = "Working condition uncertain";
        }
        
        return implode('; ', $reasons);
    }
    
    /**
     * Use AI for intent matching (placeholder for future implementation)
     * 
     * @param string $searchTerm
     * @param string $title
     * @param string $description
     * @return bool
     */
    private function aiIntentMatch(string $searchTerm, string $title, string $description): bool
    {
        // Placeholder for AI integration
        // This would call an AI service to determine intent match
        
        try {
            $prompt = "Does this listing match the search intent?\n\n";
            $prompt .= "Search term: {$searchTerm}\n";
            $prompt .= "Title: {$title}\n";
            $prompt .= "Description: {$description}\n\n";
            $prompt .= "Respond with only 'yes' or 'no'.";
            
            // This is a placeholder - actual implementation would depend on AI provider
            // return $this->callAiService($prompt) === 'yes';
            
            $this->logInfo('AI intent matching not implemented', [
                'search_term' => $searchTerm,
                'title' => $title
            ]);
            
            return false;
        } catch (\Exception $e) {
            $this->logError('AI intent matching failed', [
                'error' => $e->getMessage(),
                'search_term' => $searchTerm
            ], $e);
            
            return false;
        }
    }
    
    /**
     * Use AI for working condition assessment (placeholder for future implementation)
     * 
     * @param string $description
     * @return bool|null
     */
    private function aiWorkingConditionAssessment(string $description): ?bool
    {
        // Placeholder for AI integration
        // This would call an AI service to assess working condition
        
        try {
            $prompt = "Based on this Romanian listing description, is the item likely in working condition?\n\n";
            $prompt .= "Description: {$description}\n\n";
            $prompt .= "Respond with 'working', 'broken', or 'uncertain'.";
            
            // This is a placeholder - actual implementation would depend on AI provider
            // $response = $this->callAiService($prompt);
            // return match($response) {
            //     'working' => true,
            //     'broken' => false,
            //     default => null
            // };
            
            $this->logInfo('AI working condition assessment not implemented', [
                'description_length' => mb_strlen($description)
            ]);
            
            return null;
        } catch (\Exception $e) {
            $this->logError('AI working condition assessment failed', [
                'error' => $e->getMessage()
            ], $e);
            
            return null;
        }
    }
}

/**
 * Data structure for classification results
 */
class Classification
{
    public function __construct(
        public readonly bool $matchesIntent,
        public readonly ?bool $likelyWorking,
        public readonly float $confidence,
        public readonly string $reasoning = ''
    ) {}
    
    /**
     * Check if classification is high confidence
     * 
     * @return bool
     */
    public function isHighConfidence(): bool
    {
        $threshold = config('ai.confidence_threshold', 0.7);
        return $this->confidence >= $threshold;
    }
    
    /**
     * Get working condition as string
     * 
     * @return string
     */
    public function getWorkingConditionString(): string
    {
        return match($this->likelyWorking) {
            true => 'working',
            false => 'broken',
            null => 'uncertain'
        };
    }
}

/**
 * Placeholder data structure for parsed listing (will be implemented in crawler task)
 */
class ParsedListing
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $url = '',
        public readonly ?float $price = null
    ) {}
}