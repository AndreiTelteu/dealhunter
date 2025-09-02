<?php

namespace App\Services;

use App\Services\Crawlers\ParsedListing;
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
    
    private AiService $aiService;
    
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
    
    public function __construct(AiService $aiService)
    {
        parent::__construct();
        $this->aiService = $aiService;
    }
    
    /**
     * Classify a listing for intent matching and working condition
     * 
     * @param string $searchTerm
     * @param ParsedListing $listing
     * @return Classification
     */
    public function classifyListing(string $searchTerm, ParsedListing $listing): Classification
    {
        return $this->executeWithErrorHandling(
            function () use ($searchTerm, $listing) {
                // Use AI classification if enabled, otherwise fall back to keyword-based
                if (config('features.ai_classification_enabled', true)) {
                    return $this->classifyWithAI($searchTerm, $listing);
                } else {
                    return $this->classifyWithKeywords($searchTerm, $listing);
                }
            },
            [
                'search_term' => $searchTerm,
                'title' => $listing->title,
                'external_id' => $listing->externalId
            ],
            'classify_listing'
        );
    }
    
    /**
     * Classify using AI service
     * 
     * @param string $searchTerm
     * @param ParsedListing $listing
     * @return Classification
     */
    private function classifyWithAI(string $searchTerm, ParsedListing $listing): Classification
    {
        try {
            $aiResult = $this->aiService->comprehensiveClassification($searchTerm, $listing);
            
            // Combine AI results with keyword analysis for better accuracy
            $keywordResult = $this->classifyWithKeywords($searchTerm, $listing);
            
            // Use AI results but boost confidence if keyword analysis agrees
            $finalConfidence = $aiResult['confidence'];
            if ($aiResult['matches_intent'] === $keywordResult->matchesIntent) {
                $finalConfidence = min(1.0, $finalConfidence + 0.1);
            }
            if ($aiResult['likely_working'] === $keywordResult->likelyWorking) {
                $finalConfidence = min(1.0, $finalConfidence + 0.1);
            }
            
            $this->logDebug('AI classification completed', [
                'search_term' => $searchTerm,
                'title' => $listing->title,
                'ai_intent' => $aiResult['matches_intent'],
                'ai_working' => $aiResult['likely_working'],
                'ai_confidence' => $aiResult['confidence'],
                'keyword_intent' => $keywordResult->matchesIntent,
                'keyword_working' => $keywordResult->likelyWorking,
                'final_confidence' => $finalConfidence
            ]);
            
            return new Classification(
                matchesIntent: $aiResult['matches_intent'],
                likelyWorking: $aiResult['likely_working'],
                confidence: $finalConfidence,
                reasoning: $aiResult['reasoning']
            );
            
        } catch (\Throwable $e) {
            $this->logWarning('AI classification failed, falling back to keywords', [
                'search_term' => $searchTerm,
                'title' => $listing->title,
                'error' => $e->getMessage()
            ]);
            
            return $this->classifyWithKeywords($searchTerm, $listing);
        }
    }
    
    /**
     * Classify using keyword-based analysis
     * 
     * @param string $searchTerm
     * @param ParsedListing $listing
     * @return Classification
     */
    private function classifyWithKeywords(string $searchTerm, ParsedListing $listing): Classification
    {
        $intentMatch = $this->matchesIntent($searchTerm, $listing->title, $listing->description ?? '');
        $workingCondition = $this->assessWorkingCondition($listing->description ?? '');
        $confidence = $this->calculateConfidence([
            'intent_match' => $intentMatch,
            'working_condition' => $workingCondition,
            'title_quality' => $this->assessTitleQuality($listing->title),
            'description_quality' => $this->assessDescriptionQuality($listing->description ?? ''),
        ]);
        
        $this->logDebug('Keyword classification completed', [
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
        
        // Keyword-based matching only in this method
        // AI integration is handled at the higher level
        
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
        
        // Keyword-based assessment only in this method
        // AI integration is handled at the higher level
        
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
    

}



