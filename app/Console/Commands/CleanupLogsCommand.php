<?php

namespace App\Console\Commands;

use App\Services\CrawlLogService;
use App\Services\SystemHealthService;
use Illuminate\Console\Command;

class CleanupLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup 
                            {--crawl-days=30 : Days to keep crawl logs}
                            {--health-days=7 : Days to keep health check logs}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old crawl logs and health check records';

    /**
     * Execute the console command.
     */
    public function handle(CrawlLogService $crawlLogService, SystemHealthService $healthService): int
    {
        $crawlDays = (int) $this->option('crawl-days');
        $healthDays = (int) $this->option('health-days');
        $isDryRun = $this->option('dry-run');
        
        $this->info('Starting log cleanup...');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual deletion will be performed');
        }
        
        try {
            // Show what would be deleted
            $crawlLogsToDelete = \App\Models\CrawlLog::where('started_at', '<', now()->subDays($crawlDays))->count();
            $healthLogsToDelete = \App\Models\SystemHealth::where('checked_at', '<', now()->subDays($healthDays))->count();
            
            $this->info("Crawl logs to delete (older than {$crawlDays} days): {$crawlLogsToDelete}");
            $this->info("Health logs to delete (older than {$healthDays} days): {$healthLogsToDelete}");
            
            if ($isDryRun) {
                $this->info('Dry run completed. No logs were deleted.');
                return 0;
            }
            
            if ($crawlLogsToDelete === 0 && $healthLogsToDelete === 0) {
                $this->info('No logs to delete.');
                return 0;
            }
            
            // Confirm deletion
            if (!$this->confirm('Do you want to proceed with the deletion?')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
            
            // Perform cleanup
            $crawlLogsDeleted = $crawlLogService->cleanupOldLogs($crawlDays);
            $healthLogsDeleted = $healthService->cleanupOldHealthChecks($healthDays);
            
            $this->info("Cleanup completed:");
            $this->info("- Deleted {$crawlLogsDeleted} crawl log records");
            $this->info("- Deleted {$healthLogsDeleted} health check records");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            return 1;
        }
    }
}
