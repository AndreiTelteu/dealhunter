<?php

namespace Tests\Feature;

use App\Jobs\RunDealCrawl;
use App\Models\CrawlLog;
use App\Models\SystemHealth;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    private function createUser(string $email = 'admin-user@example.test', string $name = 'Admin Test User'): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'secret-password',
            'email_verified_at' => now(),
        ]);
    }

    private function createAdmin(string $email = 'admin@example.test', string $name = 'Admin Test Admin'): User
    {
        $admin = $this->createUser($email, $name);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function createCrawlLog(array $attributes = []): CrawlLog
    {
        return CrawlLog::create(array_merge([
            'type' => 'crawl',
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(5),
            'duration_ms' => 300000,
            'hunted_deals_processed' => 5,
            'hunted_deals_failed' => 0,
            'total_listings_found' => 120,
            'new_deals_created' => 10,
            'deals_updated' => 4,
            'snapshots_created' => 40,
            'total_errors' => 0,
            'success_rate' => 100,
            'listings_per_second' => 0.4,
            'configuration' => ['max_pages_per_search' => 3, 'ai_classification_enabled' => true],
            'errors' => [],
            'notes' => null,
            'triggered_by' => 'scheduler',
            'user_id' => null,
        ], $attributes));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_non_admin_user_gets_forbidden(): void
    {
        $this->actingAs($this->createUser())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_sees_the_dashboard_component_with_defaulted_props(): void
    {
        $this->actingAs($this->createAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('crawlStats.totalCrawls', 0)
                ->where('crawlStats.successfulCrawls', 0)
                ->where('crawlStats.failedCrawls', 0)
                ->where('crawlStats.partialSuccessCrawls', 0)
                ->where('crawlStats.totalListingsFound', 0)
                ->where('crawlStats.totalDealsCreated', 0)
                ->where('crawlStats.totalDealsUpdated', 0)
                ->where('crawlStats.totalSnapshotsCreated', 0)
                ->where('crawlStats.averageDurationMs', null)
                ->where('crawlStats.averageSuccessRate', null)
                ->where('crawlStats.lastCrawlAt', null)
                ->where('systemHealth.overallStatus', 'unknown')
                ->where('systemHealth.summary.total', 4)
                ->where('systemHealth.summary.healthy', 0)
                ->where('systemHealth.summary.warning', 0)
                ->where('systemHealth.summary.critical', 0)
                ->where('systemHealth.summary.unknown', 4)
                ->where('systemHealth.lastCheckLabel', null)
                ->where('systemComponents.database', null)
                ->where('systemComponents.mcp', null)
                ->where('systemComponents.crawler', null)
                ->where('systemComponents.ai', null)
                ->where('recentCrawls', [])
                ->where('activeHuntedDeals', 0)
                ->where('totalDeals', 0)
                ->has('crawlerConfig.maxPagesPerSearch')
                ->has('crawlerConfig.requestDelayMs')
                ->has('crawlerConfig.maxListingsPerRun')
                ->has('crawlerConfig.mcpEndpoint')
                ->where('crawlerConfig.aiClassificationEnabled', true)
                ->where('huntedDealsOptions', [])
                ->has('links.systemHealth')
                ->has('links.crawlLogs')
                ->has('links.configuration')
                ->has('links.runHealthCheck')
                ->has('links.triggerCrawl'));
    }

    public function test_crawl_logs_renders_component_with_pagination_and_filters(): void
    {
        $this->createCrawlLog(['type' => 'manual_crawl', 'status' => 'completed']);
        $this->createCrawlLog(['status' => 'failed']);

        $this->actingAs($this->createAdmin())
            ->get(route('admin.crawl-logs', ['status' => 'completed']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CrawlLogs')
                ->where('filters.status', 'completed')
                ->where('filters.type', null)
                ->where('filters.dateFrom', null)
                ->where('filters.dateTo', null)
                ->where('filters.hasActiveFilters', true)
                ->where('logs.meta.currentPage', 1)
                ->where('logs.meta.perPage', 20)
                ->where('logs.meta.total', 1)
                ->where('logs.data.0.typeLabel', 'Manual crawl')
                ->where('logs.data.0.status', 'completed')
                ->where('logs.data.0.huntedDealsProcessed', 5)
                ->where('logs.data.0.totalListingsFound', 120)
                ->has('logs.data.0.startedAtDate')
                ->has('logs.data.0.startedAtTime')
                ->has('logs.data.0.formattedDuration')
                ->has('logs.data.0.showUrl')
                ->has('links.index')
                ->has('links.dashboard'));
    }

    public function test_crawl_log_detail_renders_component_with_serialized_log(): void
    {
        $admin = $this->createAdmin();
        $crawlLog = $this->createCrawlLog([
            'type' => 'manual_crawl',
            'status' => 'completed',
            'total_errors' => 1,
            'errors' => ['Connection timed out'],
            'notes' => 'Manual run',
            'triggered_by' => 'admin',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.crawl-logs.show', $crawlLog))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CrawlLogDetail')
                ->where('crawlLog.id', $crawlLog->id)
                ->where('crawlLog.typeLabel', 'Manual crawl')
                ->where('crawlLog.status', 'completed')
                ->where('crawlLog.totalErrors', 1)
                ->where('crawlLog.notes', 'Manual run')
                ->where('crawlLog.huntedDealsProcessed', 5)
                ->where('crawlLog.huntedDealsFailed', 0)
                ->where('crawlLog.totalListingsFound', 120)
                ->where('crawlLog.newDealsCreated', 10)
                ->where('crawlLog.dealsUpdated', 4)
                ->where('crawlLog.snapshotsCreated', 40)
                ->where('crawlLog.successRate', 100)
                ->where('crawlLog.errors', ['Connection timed out'])
                ->where('crawlLog.configuration.0.key', 'max_pages_per_search')
                ->where('crawlLog.configuration.0.value', '3')
                ->where('crawlLog.configuration.1.key', 'ai_classification_enabled')
                ->where('crawlLog.configuration.1.value', 'Activată')
                ->where('crawlLog.triggeredByLabel', 'Admin')
                ->where('crawlLog.userName', 'Admin Test Admin')
                ->has('crawlLog.startedAtLabel')
                ->has('crawlLog.completedAtLabel')
                ->has('crawlLog.formattedDuration')
                ->has('links.crawlLogs'));
    }

    public function test_system_health_renders_component_with_overall_summary(): void
    {
        config()->set('features.ai_classification_enabled', false);
        config()->set('crawler.mcp_playwright_endpoint', null);

        $this->actingAs($this->createAdmin())
            ->get(route('admin.system-health'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/SystemHealth')
                ->where('overallHealth.summary.total', 4)
                ->where('overallHealth.summary.healthy', 1)
                ->has('overallHealth.overallStatus')
                ->has('overallHealth.summary.warning')
                ->has('overallHealth.summary.critical')
                ->has('overallHealth.summary.unknown')
                ->has('overallHealth.lastCheckLabel')
                ->where('healthResults', fn ($results) => count($results) === 4)
                ->where('healthHistory', fn ($history) => $history instanceof Collection)
                ->has('links.dashboard')
                ->has('links.runHealthCheck')
                ->has('links.cleanupLogs'));
    }

    public function test_configuration_renders_component_and_never_leaks_secrets(): void
    {
        config()->set('ai.api_key', 'super-secret-test-key');
        config()->set('crawler.mcp_playwright_token', 'super-secret-token-value');

        $this->actingAs($this->createAdmin())
            ->get(route('admin.configuration'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Configuration')
                ->has('config.crawler.maxPagesPerSearch')
                ->has('config.crawler.requestDelayMs')
                ->has('config.crawler.maxListingsPerRun')
                ->has('config.crawler.mcpPlaywrightEndpoint')
                ->has('config.crawler.userAgent')
                ->has('config.features.aiClassificationEnabled')
                ->has('config.features.detailPageCrawling')
                ->has('config.features.imageUrlExtraction')
                ->has('config.features.sellerInfoExtraction')
                ->has('config.ai.provider')
                ->has('config.ai.model')
                ->has('config.ai.confidenceThreshold')
                ->where('config.currency.defaultCurrency', 'RON')
                ->where('config.currency.eurToRonRate', 4.95)
                ->where('config.currency.usdToRonRate', 4.5)
                ->missing('config.ai.apiKey')
                ->missing('config.crawler.mcpPlaywrightToken')
                ->has('links.dashboard'))
            ->assertDontSee('super-secret-test-key')
            ->assertDontSee('super-secret-token-value');
    }

    public function test_trigger_crawl_dispatches_a_job_and_redirects_with_flash(): void
    {
        Queue::fake();

        $this->actingAs($this->createAdmin())
            ->post(route('admin.trigger-crawl'), [
                'hunted_deal_id' => null,
                'dry_run' => false,
                'notes' => 'Test manual crawl',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(RunDealCrawl::class);
    }

    public function test_run_health_check_redirects_with_flash(): void
    {
        config()->set('features.ai_classification_enabled', false);
        config()->set('crawler.mcp_playwright_endpoint', null);

        $this->actingAs($this->createAdmin())
            ->post(route('admin.run-health-check'))
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    public function test_cleanup_logs_deletes_old_records_and_redirects_with_flash(): void
    {
        $this->createCrawlLog(['started_at' => now()->subDays(60), 'completed_at' => now()->subDays(60)]);
        SystemHealth::create([
            'component' => 'database',
            'status' => 'healthy',
            'message' => 'Old check',
            'checked_at' => now()->subDays(30),
        ]);

        $this->actingAs($this->createAdmin())
            ->post(route('admin.cleanup-logs'), [
                'crawl_logs_days' => 30,
                'health_logs_days' => 7,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('system_healths', ['component' => 'database']);
    }
}
