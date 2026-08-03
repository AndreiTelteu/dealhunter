<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RunDealCrawl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** The crawl can enrich many listing detail pages. */
    public int $timeout = 900;

    /** A retry may duplicate ingestion work, so failed jobs require an explicit retry. */
    public int $tries = 1;

    public function __construct(
        public readonly ?int $huntedDealId = null,
        public readonly bool $dryRun = false,
        public readonly ?int $triggeredByUserId = null,
    ) {}

    public function handle(): void
    {
        $options = [];

        if ($this->dryRun) {
            $options['--dry-run'] = true;
        }

        if ($this->huntedDealId !== null) {
            $options['--hunted-deal'] = $this->huntedDealId;
        }

        $exitCode = Artisan::call('deals:crawl', $options);
        if ($exitCode !== 0) {
            throw new RuntimeException('Queued deal crawl failed with exit code '.$exitCode.'.');
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('crawler')->error('Queued deal crawl failed', [
            'hunted_deal_id' => $this->huntedDealId,
            'dry_run' => $this->dryRun,
            'triggered_by_user_id' => $this->triggeredByUserId,
            'error' => $exception->getMessage(),
        ]);
    }
}
