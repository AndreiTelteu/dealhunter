<?php

namespace App\Console\Commands;

use App\Models\Deal;
use App\Services\Crawlers\ParsedListing;
use App\Services\IntentClassifierService;
use Illuminate\Console\Command;

class ReclassifyDealsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deals:reclassify 
                            {--hunted-deal= : Specific hunted deal ID to reclassify}
                            {--limit=100 : Maximum number of deals to process}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Reclassify all deals, even those with existing classifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reclassify existing deals using AI';

    /**
     * Execute the console command.
     */
    public function handle(IntentClassifierService $classifier): int
    {
        $huntedDealId = $this->option('hunted-deal');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Starting deal reclassification...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Build query
        $query = Deal::with('huntedDeal');

        if ($huntedDealId) {
            $query->where('hunted_deal_id', $huntedDealId);
            $this->line("Filtering to hunted deal ID: {$huntedDealId}");
        }

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('matches_intent')
                    ->orWhereNull('intent_score')
                    ->orWhereNull('likely_working')
                    ->orWhereNull('confidence');
            });
            $this->line('Only processing deals without existing classifications');
        } else {
            $this->line('Processing all deals (force mode)');
        }

        $totalDeals = $query->count();
        $this->line("Found {$totalDeals} deals to process");

        if ($totalDeals === 0) {
            $this->info('No deals to process');

            return 0;
        }

        if ($limit > 0 && $totalDeals > $limit) {
            $this->line("Limiting to {$limit} deals");
            $query->limit($limit);
        }

        $deals = $query->get();
        $processed = 0;
        $updated = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($deals->count());
        $progressBar->start();

        foreach ($deals as $deal) {
            try {
                $processed++;

                // Create ParsedListing from deal data
                $listing = new ParsedListing(
                    externalId: $deal->external_id,
                    url: $deal->url,
                    title: $deal->title,
                    priceRaw: $deal->price_raw,
                    priceAmount: $deal->price_amount,
                    priceCurrency: $deal->price_currency,
                    description: $deal->description ?? '',
                    location: $deal->location,
                    sellerName: $deal->seller_name,
                    sellerUrl: $deal->seller_url,
                    postedAt: $deal->posted_at?->toISOString()
                );

                // Classify the listing
                $classification = $classifier->classifyListing($deal->huntedDeal->search_term, $listing);

                if (! $dryRun) {
                    // Update the deal
                    $deal->update([
                        'matches_intent' => $classification->matchesIntent,
                        'intent_score' => $classification->intentScore,
                        'likely_working' => $classification->likelyWorking,
                        'confidence' => $classification->confidence,
                    ]);

                    // Update latest snapshot if it exists
                    $latestSnapshot = $deal->snapshots()->first();
                    if ($latestSnapshot) {
                        $latestSnapshot->update([
                            'matches_intent' => $classification->matchesIntent,
                            'intent_score' => $classification->intentScore,
                            'likely_working' => $classification->likelyWorking,
                            'confidence' => $classification->confidence,
                        ]);
                    }
                }

                $updated++;

                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->line("Deal {$deal->id}: {$deal->title}");
                    $this->line('  Intent: '.($classification->matchesIntent ? 'Match' : 'No match').' (score: '.($classification->intentScore ?? '-').')');
                    $this->line('  Working: '.$classification->getWorkingConditionString());
                    $this->line('  Confidence: '.number_format($classification->confidence, 2));
                }

            } catch (\Throwable $e) {
                $errors++;

                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->error("Error processing deal {$deal->id}: {$e->getMessage()}");
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Reclassification completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total processed', $processed],
                ['Successfully updated', $updated],
                ['Errors', $errors],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run - no changes were made');
            $this->line('Run without --dry-run to apply changes');
        }

        return $errors > 0 ? 1 : 0;
    }
}
