<?php

namespace App\Services;

use App\Services\Crawlers\ParsedListing;

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

    /**
     * Equivalent terms used by the deterministic intent matcher.
     *
     * Keep groups small and product-focused so broad category matches do not
     * override the AI classification for ambiguous listings.
     *
     * @var array<string, list<string>>
     */
    private const INTENT_SYNONYM_GROUPS = [
        'telefon' => ['telefon', 'telefoane', 'smartphone', 'mobil', 'mobile', 'cellphone'],
        'laptop' => ['laptop', 'notebook', 'ultrabook', 'portabil'],
        'calculator' => ['calculator', 'pc', 'desktop', 'unitate', 'sistem'],
        'casti' => ['casti', 'headphones', 'headset', 'earbuds', 'buds', 'audio'],
        'televizor' => ['televizor', 'tv', 'smarttv', 'television'],
        'frigider' => ['frigider', 'combina', 'refrigerator'],
        'masina' => ['masina', 'auto', 'autoturism', 'vehicul', 'car'],
        'bicicleta' => ['bicicleta', 'biciclete', 'bike', 'bicycle'],
        'motocicleta' => ['motocicleta', 'motor', 'moto', 'scooter'],
        'canapea' => ['canapea', 'sofa', 'coltar', 'divan'],
        'apartament' => ['apartament', 'garsoniera', 'locuinta', 'studio'],
        'inchiriere' => ['inchiriere', 'chirie', 'rent', 'deinchiriat'],
        'vanzare' => ['vanzare', 'devanzare', 'cumparare'],
    ];

    /**
     * Accessory keywords: when present alongside the search term, the listing
     * is likely a peripheral item (cable, case, charger) rather than the
     * target product itself.
     */
    private const ACCESSORY_KEYWORDS = [
        'cablu', 'cabluri', 'husa', 'huse', 'incarcator', 'alimentator', 'alimentare',
        'adaptor', 'adaptoare', 'suport', 'stand', 'toc', 'folie', 'telecomanda',
        'maner', 'capac', 'rama', 'bateria', 'acumulator', 'casti cu', 'bratara',
    ];

    /** @var list<string> */
    private const INTENT_STOP_WORDS = [
        'si', 'sau', 'cu', 'de', 'la', 'in', 'pe', 'din', 'pentru', 'the', 'and', 'for', 'new', 'nou', 'noua',
    ];

    public function __construct(AiService $aiService)
    {
        parent::__construct();
        $this->aiService = $aiService;
    }

    /**
     * Classify a listing for intent matching and working condition
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
                'external_id' => $listing->externalId,
            ],
            'classify_listing'
        );
    }

    /**
     * Classify using AI service
     */
    private function classifyWithAI(string $searchTerm, ParsedListing $listing): Classification
    {
        try {
            $aiResult = $this->aiService->comprehensiveClassification($searchTerm, $listing);

            $intentScore = $aiResult['intent_score'];
            $matchesIntent = $aiResult['matches_intent'];
            $keywordWorking = $this->assessWorkingCondition($listing->description ?? '');
            $likelyWorking = $aiResult['likely_working'] ?? $keywordWorking;

            $finalConfidence = $aiResult['confidence'];
            if ($aiResult['likely_working'] === $keywordWorking) {
                $finalConfidence = min(1.0, $finalConfidence + 0.1);
            }

            $this->logDebug('AI classification completed', [
                'search_term' => $searchTerm,
                'title' => $listing->title,
                'intent_score' => $intentScore,
                'final_intent' => $matchesIntent,
                'ai_working' => $aiResult['likely_working'],
                'final_working' => $likelyWorking,
                'ai_confidence' => $aiResult['confidence'],
                'final_confidence' => $finalConfidence,
            ]);

            return new Classification(
                matchesIntent: $matchesIntent,
                likelyWorking: $likelyWorking,
                confidence: $finalConfidence,
                reasoning: $aiResult['reasoning'],
                intentScore: $intentScore,
            );

        } catch (\Throwable $e) {
            $this->logWarning('AI classification failed, falling back to keywords', [
                'search_term' => $searchTerm,
                'title' => $listing->title,
                'error' => $e->getMessage(),
            ]);

            return $this->classifyWithKeywords($searchTerm, $listing);
        }
    }

    /**
     * Classify using keyword-based analysis
     */
    private function classifyWithKeywords(string $searchTerm, ParsedListing $listing): Classification
    {
        $intentScore = $this->keywordIntentScore($searchTerm, $listing->title, $listing->description ?? '');
        $intentMatch = $intentScore >= (int) config('ai.intent_score_threshold', 60);
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
            'intent_score' => $intentScore,
            'intent_match' => $intentMatch,
            'working_condition' => $workingCondition,
            'confidence' => $confidence,
        ]);

        return new Classification(
            matchesIntent: $intentMatch,
            likelyWorking: $workingCondition,
            confidence: $confidence,
            reasoning: $this->generateReasoning($intentMatch, $workingCondition, $searchTerm, $listing),
            intentScore: $intentScore,
        );
    }

    /**
     * Heuristic intent score (0-100) based on keyword matching.
     *
     * Exact title match scores 100. Synonym-normalized term overlap is
     * scaled to at most 80. Description-only matches are capped lower so
     * accessories merely mentioned in text do not pass the threshold.
     */
    private function keywordIntentScore(string $searchTerm, string $title, string $description): int
    {
        $normalizedSearchTerm = $this->normalizeIntentText($searchTerm);
        $normalizedTitle = $this->normalizeIntentText($title);
        $normalizedDescription = $this->normalizeIntentText($description);

        if ($normalizedSearchTerm === '') {
            return 0;
        }

        if (str_contains($normalizedTitle, $normalizedSearchTerm)) {
            return $this->titleMentionsAccessory($normalizedTitle) ? 30 : 100;
        }

        $intentTerms = $this->intentTerms($normalizedSearchTerm);
        if ($intentTerms === []) {
            return 0;
        }

        $titleRatio = $this->intentMatchRatio($intentTerms, $this->intentTerms($normalizedTitle));
        if ($titleRatio > 0) {
            return (int) round(min(1.0, $titleRatio) * 80);
        }

        if (str_contains($normalizedDescription, $normalizedSearchTerm)) {
            return 45;
        }

        $descriptionRatio = $this->intentMatchRatio($intentTerms, $this->intentTerms($normalizedDescription));
        if ($descriptionRatio >= 0.8) {
            return 40;
        }

        return (int) round($descriptionRatio * 30);
    }

    private function titleMentionsAccessory(string $normalizedTitle): bool
    {
        foreach (self::ACCESSORY_KEYWORDS as $accessory) {
            if (str_contains($normalizedTitle, $this->normalizeIntentText($accessory))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeIntentText(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = strtr($value, ['ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't']);

        return trim((string) preg_replace('/[^a-z0-9]+/u', ' ', $value));
    }

    /**
     * @return list<string>
     */
    private function intentTerms(string $value): array
    {
        $synonyms = $this->intentSynonyms();
        $terms = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_map(
            fn (string $term): string => $synonyms[$term] ?? $term,
            array_filter($terms, fn (string $term): bool => ! in_array($term, self::INTENT_STOP_WORDS, true) && (strlen($term) >= 3 || ctype_digit($term)))
        )));
    }

    /**
     * @param  list<string>  $expectedTerms
     * @param  list<string>  $listingTerms
     */
    private function intentMatchRatio(array $expectedTerms, array $listingTerms): float
    {
        if ($expectedTerms === []) {
            return 0.0;
        }

        return count(array_intersect($expectedTerms, $listingTerms)) / count($expectedTerms);
    }

    /**
     * @return array<string, string>
     */
    private function intentSynonyms(): array
    {
        $synonyms = [];

        foreach (self::INTENT_SYNONYM_GROUPS as $canonicalTerm => $variants) {
            foreach ($variants as $variant) {
                $synonyms[$variant] = $canonicalTerm;
            }
        }

        return $synonyms;
    }

    /**
     * Assess working condition based on description analysis
     *
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
     */
    private function assessTitleQuality(string $title): float
    {
        $length = mb_strlen($title, 'UTF-8');

        // Optimal title length is 20-80 characters
        if ($length < 10) {
            return 0.2;
        }
        if ($length < 20) {
            return 0.5;
        }
        if ($length <= 80) {
            return 1.0;
        }
        if ($length <= 120) {
            return 0.8;
        }

        return 0.6; // Very long titles might be spam
    }

    /**
     * Assess description quality
     */
    private function assessDescriptionQuality(string $description): float
    {
        $length = mb_strlen($description, 'UTF-8');

        // Longer descriptions generally provide more information
        if ($length < 50) {
            return 0.3;
        }
        if ($length < 100) {
            return 0.6;
        }
        if ($length <= 500) {
            return 1.0;
        }
        if ($length <= 1000) {
            return 0.9;
        }

        return 0.7; // Very long descriptions might be copy-paste
    }

    /**
     * Generate human-readable reasoning for classification
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
            $reasons[] = 'Likely in working condition';
        } elseif ($workingCondition === false) {
            $reasons[] = 'Likely broken or for parts';
        } else {
            $reasons[] = 'Working condition uncertain';
        }

        return implode('; ', $reasons);
    }
}
