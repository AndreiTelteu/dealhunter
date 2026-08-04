<?php

namespace App\Services;

class Classification
{
    public function __construct(
        public readonly bool $matchesIntent,
        public readonly ?bool $likelyWorking,
        public readonly float $confidence,
        public readonly string $reasoning = '',
        public readonly ?int $intentScore = null,
    ) {}

    public function isHighConfidence(): bool
    {
        $threshold = config('ai.confidence_threshold', 0.7);

        return $this->confidence >= $threshold;
    }

    /**
     * Check if the listing meets the intent score threshold.
     */
    public function isExactMatch(): bool
    {
        if ($this->intentScore === null) {
            return $this->matchesIntent;
        }

        return $this->intentScore >= (int) config('ai.intent_score_threshold', 60);
    }

    public function getWorkingConditionString(): string
    {
        return match ($this->likelyWorking) {
            true => 'working',
            false => 'broken',
            null => 'uncertain'
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'matches_intent' => $this->matchesIntent,
            'intent_score' => $this->intentScore,
            'is_exact_match' => $this->isExactMatch(),
            'likely_working' => $this->likelyWorking,
            'confidence' => $this->confidence,
            'reasoning' => $this->reasoning,
            'is_high_confidence' => $this->isHighConfidence(),
            'working_condition_string' => $this->getWorkingConditionString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            matchesIntent: (bool) ($data['matches_intent'] ?? false),
            likelyWorking: isset($data['likely_working']) ? (bool) $data['likely_working'] : null,
            confidence: (float) ($data['confidence'] ?? 0.0),
            reasoning: (string) ($data['reasoning'] ?? ''),
            intentScore: isset($data['intent_score']) ? (int) $data['intent_score'] : null,
        );
    }
}
