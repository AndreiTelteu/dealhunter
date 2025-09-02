# AI Classification Integration

This document describes the AI classification system implemented for the OLX Deal Hunter application.

## Overview

The AI classification system provides intelligent analysis of listings to determine:
1. **Intent Matching**: Whether a listing matches the user's search intent
2. **Working Condition**: Whether an item is likely in working condition based on Romanian keywords and AI analysis

## Architecture

### Core Components

1. **AiService**: Main AI integration service supporting multiple providers (OpenAI, Anthropic)
2. **IntentClassifierService**: High-level classification service with keyword fallback
3. **Classification**: Data structure for classification results
4. **DealIngestionService**: Integrates classification into the deal processing pipeline

### Classification Flow

```
Listing Data → IntentClassifierService → AI Service (if enabled) → Classification Result
                                      ↓
                                   Keyword Analysis (fallback)
```

## Configuration

### Environment Variables

```env
# Enable/disable AI classification
AI_CLASSIFICATION_ENABLED=true

# AI provider configuration
AI_PROVIDER=openai                    # openai or anthropic
AI_MODEL=gpt-3.5-turbo               # Provider-specific model
AI_API_KEY=your_api_key_here         # API key for the provider

# Classification settings
AI_CONFIDENCE_THRESHOLD=0.7          # Minimum confidence for high-confidence classification
```

### Supported AI Providers

#### OpenAI
- Models: `gpt-3.5-turbo`, `gpt-3.5-turbo-16k`, `gpt-4`, `gpt-4-turbo-preview`
- API Key: OpenAI API key

#### Anthropic Claude
- Models: `claude-3-haiku-20240307`, `claude-3-sonnet-20240229`, `claude-3-opus-20240229`
- API Key: Anthropic API key

## Features

### Intent Matching

Determines if a listing matches the user's search intent by analyzing:
- Direct keyword matches in title/description
- Semantic similarity (AI-powered)
- Context and relevance

### Working Condition Assessment

Analyzes Romanian listings to determine working condition:

**Broken/Defective Keywords:**
- stricat, defect, pentru piese, nu funcționează
- spart, deteriorat, nu merge, blocat
- accident, vandalizat, inundat, ars

**Working Keywords:**
- funcțional, merge perfect, stare bună
- ca nou, testat, garanție, impecabil

**Uncertain Keywords:**
- nu știu, posibil, cred că, nu garantez

### Confidence Scoring

Classification confidence is calculated based on:
- Intent match strength (40% weight)
- Working condition certainty (30% weight)
- Title quality (20% weight)
- Description quality (10% weight)

## Usage

### Command Line Testing

```bash
# Test AI provider connection
php artisan ai:test-classification --test-connection

# Test classification with sample data
php artisan ai:test-classification \
  --search-term="laptop" \
  --title="Laptop Dell Latitude" \
  --description="Laptop functional, stare buna"

# Test with broken item
php artisan ai:test-classification \
  --search-term="laptop" \
  --title="Laptop pentru piese" \
  --description="Laptop stricat, nu porneste"
```

### Reclassify Existing Deals

```bash
# Dry run to see what would be processed
php artisan deals:reclassify --dry-run --limit=10

# Reclassify deals without existing classifications
php artisan deals:reclassify --limit=100

# Force reclassification of all deals
php artisan deals:reclassify --force --limit=50

# Reclassify specific hunted deal
php artisan deals:reclassify --hunted-deal=123
```

### Web Interface

Visit `/ai-classification` to access the testing interface where you can:
- Test AI provider connection
- Test classification with custom inputs
- Compare AI vs keyword-based results
- View detailed classification reasoning

### Programmatic Usage

```php
use App\Services\IntentClassifierService;
use App\Services\Crawlers\ParsedListing;

// Inject the service
public function __construct(IntentClassifierService $classifier)
{
    $this->classifier = $classifier;
}

// Create a listing
$listing = new ParsedListing(
    externalId: 'test-123',
    url: 'https://example.com',
    title: 'Laptop Dell Latitude',
    description: 'Laptop functional, stare buna'
);

// Classify the listing
$classification = $this->classifier->classifyListing('laptop', $listing);

// Access results
$matchesIntent = $classification->matchesIntent;      // bool
$likelyWorking = $classification->likelyWorking;     // bool|null
$confidence = $classification->confidence;           // float 0.0-1.0
$reasoning = $classification->reasoning;             // string
$isHighConfidence = $classification->isHighConfidence(); // bool
```

## Integration Points

### Deal Ingestion

The classification system is automatically integrated into the deal ingestion process:

1. When new deals are created, they are automatically classified
2. When existing deals are updated with significant changes, they are re-classified
3. Classification results are stored in both `deals` and `deal_snapshots` tables

### Database Schema

Classification results are stored in these fields:
- `matches_intent`: boolean
- `likely_working`: boolean (nullable)
- `confidence`: decimal(3,2)

## Error Handling

The system includes comprehensive error handling:

1. **AI Service Failures**: Automatic fallback to keyword-based classification
2. **API Rate Limits**: Exponential backoff and retry logic
3. **Invalid Responses**: JSON parsing with text fallback
4. **Network Issues**: Timeout handling and graceful degradation

## Performance Considerations

### Caching

AI responses are cached for 1 hour using the following cache key format:
```
ai_classification:{type}:{provider}:{model}:{content_hash}
```

### Rate Limiting

- Respects AI provider rate limits
- Implements request delays and backoff strategies
- Processes deals in batches to avoid overwhelming the API

## Monitoring

### Logging

All classification operations are logged with structured data:
- Service: `ai` or `classifier` log channels
- Context: search terms, titles, confidence scores
- Errors: Full exception details and context

### Metrics

Track these metrics for monitoring:
- Classification success/failure rates
- Average confidence scores
- AI vs keyword agreement rates
- Processing times and API response times

## Troubleshooting

### Common Issues

1. **AI Classification Disabled**
   - Check `AI_CLASSIFICATION_ENABLED=true` in `.env`
   - Verify API key is set and valid

2. **Low Confidence Scores**
   - Review and tune the confidence threshold
   - Check if listings have sufficient description text
   - Verify Romanian keyword lists are comprehensive

3. **API Errors**
   - Check API key validity and billing status
   - Verify network connectivity
   - Review rate limiting settings

### Debug Commands

```bash
# Test connection
php artisan ai:test-classification --test-connection

# Verbose classification test
php artisan ai:test-classification -v

# Check logs
tail -f storage/logs/laravel.log | grep -E "(ai|classifier)"
```

## Future Enhancements

Potential improvements to consider:
1. Support for additional AI providers (Google Gemini, local models)
2. Custom fine-tuned models for Romanian marketplace listings
3. Batch processing for improved efficiency
4. Real-time classification confidence feedback
5. A/B testing framework for classification strategies