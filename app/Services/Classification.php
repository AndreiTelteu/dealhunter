<?php

namespace App\Services;

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
    
    /**
     * Convert to array for storage or API responses
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'matches_intent' => $this->matchesIntent,
            'likely_working' => $this->likelyWorking,
            'confidence' => $this->confidence,
            'reasoning' => $this->reasoning,
            'is_high_confidence' => $this->isHighConfidence(),
            'working_condition_string' => $this->getWorkingConditionString()
        ];
    }
    
    /**
     * Create from array data
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            matchesIntent: (bool) ($data['matches_intent'] ?? false),
            likelyWorking: isset($data['likely_working']) ? (bool) $data['likely_working'] : null,
            confidence: (float) ($data['confidence'] ?? 0.0),
            reasoning: (string) ($data['reasoning'] ?? '')
        );
    }
}