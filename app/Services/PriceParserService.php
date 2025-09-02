<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service for parsing and normalizing price information from OLX listings
 * 
 * Handles currency detection, numeric extraction, and conversion to RON
 */
class PriceParserService extends BaseService
{
    protected string $logChannel = 'crawler';
    
    /**
     * Currency conversion rates to RON
     */
    private const CURRENCY_RATES = [
        'RON' => 1.0,
        'EUR' => 4.95, // Default rate, should be configurable
        'USD' => 4.50, // Default rate, should be configurable
        'LEI' => 1.0,  // Alternative Romanian currency notation
    ];
    
    /**
     * Currency symbols and their corresponding codes
     */
    private const CURRENCY_SYMBOLS = [
        '€' => 'EUR',
        '$' => 'USD',
        'lei' => 'RON',
        'ron' => 'RON',
        'eur' => 'EUR',
        'usd' => 'USD',
        'euro' => 'EUR',
        'dolari' => 'USD',
        'dolari americani' => 'USD',
    ];
    
    /**
     * Parse price text and extract structured price information
     * 
     * @param string $priceText Raw price text from listing
     * @return ParsedPrice
     */
    public function parsePrice(string $priceText): ParsedPrice
    {
        $originalText = trim($priceText);
        
        if (empty($originalText)) {
            return new ParsedPrice(null, 'RON', $originalText, null);
        }
        
        // Clean the text for processing
        $cleanText = $this->cleanPriceText($originalText);
        
        // Extract numeric value
        $numericValue = $this->extractNumericValue($cleanText);
        
        // Detect currency
        $currency = $this->detectCurrency($cleanText);
        
        // Convert to RON if needed
        $ronAmount = $numericValue ? $this->convertToRON($numericValue, $currency) : null;
        
        $this->logDebug('Price parsed', [
            'original' => $originalText,
            'cleaned' => $cleanText,
            'numeric' => $numericValue,
            'currency' => $currency,
            'ron_amount' => $ronAmount
        ]);
        
        return new ParsedPrice($numericValue, $currency, $originalText, $ronAmount);
    }
    
    /**
     * Convert amount to RON using configured rates
     * 
     * @param float $amount
     * @param string $currency
     * @return float
     */
    public function convertToRON(float $amount, string $currency): float
    {
        $rate = $this->getCurrencyRate($currency);
        return round($amount * $rate, 2);
    }
    
    /**
     * Extract numeric value from price text
     * 
     * @param string $text
     * @return float|null
     */
    private function extractNumericValue(string $text): ?float
    {
        // Remove currency symbols and text
        $numericText = preg_replace('/[^\d.,\s]/', '', $text);
        
        // Handle different decimal separators
        // Romanian format: 1.234,56 or 1 234,56 or 1.500 (thousands separator)
        // International format: 1,234.56 or 1234.56
        
        // Remove spaces
        $numericText = str_replace(' ', '', $numericText);
        
        if (empty($numericText)) {
            return null;
        }
        
        // Check if it looks like Romanian format with comma as decimal separator
        if (preg_match('/^\d{1,3}(?:\.\d{3})*,\d{1,2}$/', $numericText)) {
            // Romanian format: 1.234,56 or 3.999,99
            $numericText = str_replace('.', '', $numericText); // Remove thousands separator
            $numericText = str_replace(',', '.', $numericText); // Convert decimal separator
        } elseif (preg_match('/^\d+,\d{1,2}$/', $numericText)) {
            // Simple Romanian format: 1234,56 or 2300,5
            $numericText = str_replace(',', '.', $numericText);
        } elseif (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $numericText)) {
            // Romanian thousands format without decimals: 1.500, 2.300
            $numericText = str_replace('.', '', $numericText); // Remove thousands separator
        } elseif (preg_match('/^\d{1,3}(?:,\d{3})*\.\d{1,2}$/', $numericText)) {
            // International format: 1,234.56
            $numericText = str_replace(',', '', $numericText); // Remove thousands separator
        } elseif (preg_match('/^\d{1,3}(?:,\d{3})+$/', $numericText)) {
            // International thousands format without decimals: 1,500
            $numericText = str_replace(',', '', $numericText); // Remove thousands separator
        }
        
        $value = floatval($numericText);
        
        return $value > 0 ? $value : null;
    }
    
    /**
     * Detect currency from price text
     * 
     * @param string $text
     * @return string
     */
    private function detectCurrency(string $text): string
    {
        $lowerText = mb_strtolower($text, 'UTF-8');
        
        // Check for currency symbols and keywords
        foreach (self::CURRENCY_SYMBOLS as $symbol => $currency) {
            if (str_contains($lowerText, $symbol)) {
                return $currency;
            }
        }
        
        // Default to RON for Romanian site
        return 'RON';
    }
    
    /**
     * Clean price text for processing
     * 
     * @param string $text
     * @return string
     */
    private function cleanPriceText(string $text): string
    {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Remove common price prefixes/suffixes
        $text = preg_replace('/^(preț|pret|price|cost):\s*/i', '', $text);
        $text = preg_replace('/\s*(negociabil|negotiable|fix|fixed)$/i', '', $text);
        
        return $text;
    }
    
    /**
     * Get currency conversion rate
     * 
     * @param string $currency
     * @return float
     */
    private function getCurrencyRate(string $currency): float
    {
        // Use configured rates from environment if available
        $configRate = config("currency.rates.{$currency}");
        if ($configRate) {
            return (float) $configRate;
        }
        
        return self::CURRENCY_RATES[$currency] ?? 1.0;
    }
}

/**
 * Data structure for parsed price information
 */
class ParsedPrice
{
    public function __construct(
        public readonly ?float $amount,
        public readonly string $currency,
        public readonly string $rawText,
        public readonly ?float $ronAmount = null
    ) {}
    
    /**
     * Check if price was successfully parsed
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->amount !== null && $this->amount > 0;
    }
    
    /**
     * Get formatted price string
     * 
     * @return string
     */
    public function getFormattedPrice(): string
    {
        if (!$this->isValid()) {
            return $this->rawText;
        }
        
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
    
    /**
     * Get RON equivalent
     * 
     * @return float|null
     */
    public function getRonAmount(): ?float
    {
        return $this->ronAmount ?? $this->amount;
    }
}