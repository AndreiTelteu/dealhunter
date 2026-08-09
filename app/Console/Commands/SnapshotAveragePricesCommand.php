<?php

namespace App\Console\Commands;

use App\Models\HuntedDeal;
use App\Services\HuntedDealPriceSnapshotService;
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
    protected $description = 'Snapshot the average price of matching, priced deals for each hunted deal';

    /**
     * Execute the console command.
     */
    public function handle(HuntedDealPriceSnapshotService $priceSnapshots): int
    {
        $query = HuntedDeal::query();

        if ($huntedDealId = $this->option('hunted-deal')) {
            $query->where('id', $huntedDealId);
        }

        $snapshotsCreated = 0;
        $skipped = 0;

        foreach ($query->cursor() as $huntedDeal) {
            if (! $priceSnapshots->capture($huntedDeal)) {
                $skipped++;

                continue;
            }

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
