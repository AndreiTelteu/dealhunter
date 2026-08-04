<?php

namespace App\Console\Commands;

use App\Models\HuntedDeal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SnapshotAveragePricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deals:snapshot-average-prices
                            {--hunted-deal= : Snapshot only a specific hunted deal ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Snapshot the average price of matching, working, priced deals for each hunted deal';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = HuntedDeal::query();

        if ($huntedDealId = $this->option('hunted-deal')) {
            $query->where('id', $huntedDealId);
        }

        $snapshotsCreated = 0;
        $skipped = 0;

        foreach ($query->cursor() as $huntedDeal) {
            $stats = $huntedDeal->deals()
                ->where('matches_intent', true)
                ->where('likely_working', true)
                ->whereNotNull('price_amount')
                ->selectRaw('AVG(price_amount) as average_price, MIN(price_amount) as min_price, MAX(price_amount) as max_price, COUNT(*) as deals_count, MAX(price_currency) as price_currency')
                ->first();

            if (! $stats || $stats->deals_count === 0) {
                $skipped++;

                continue;
            }

            $huntedDeal->priceSnapshots()->create([
                'average_price' => round((float) $stats->average_price, 2),
                'min_price' => $stats->min_price,
                'max_price' => $stats->max_price,
                'deals_count' => $stats->deals_count,
                'price_currency' => $stats->price_currency ?? 'RON',
                'captured_at' => now(),
            ]);

            $snapshotsCreated++;
        }

        $this->info("Created {$snapshotsCreated} average price snapshot(s), skipped {$skipped} hunted deal(s) without matching priced deals.");

        Log::channel('crawler')->info('Average price snapshots completed', [
            'snapshots_created' => $snapshotsCreated,
            'hunted_deals_skipped' => $skipped,
        ]);

        return 0;
    }
}
