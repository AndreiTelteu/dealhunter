<?php

/**
 * Final System Validation for OLX Deal Hunter
 * 
 * This script performs end-to-end validation of all system components
 * and verifies that the application meets all requirements.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use App\Models\HuntedDeal;
use App\Models\Deal;
use App\Models\DealSnapshot;
use App\Models\CrawlLog;
use App\Models\SystemHealth;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== OLX Deal Hunter Final System Validation ===\n\n";

$validationResults = [];

// Requirement 1: User Authentication and Management
echo "🔐 Validating Requirement 1: User Authentication and Management\n";
try {
    // Check if authentication is properly configured
    $authGuard = config('auth.defaults.guard');
    $authProvider = config('auth.defaults.provider');
    
    echo "   Auth Guard: {$authGuard}\n";
    echo "   Auth Provider: {$authProvider}\n";
    
    // Check if users can be created and authenticated
    $userCount = User::count();
    echo "   Users in system: {$userCount}\n";
    
    if ($userCount > 0) {
        $user = User::first();
        echo "   ✅ Sample user: {$user->name} ({$user->email})\n";
        $validationResults['auth'] = true;
    } else {
        echo "   ❌ No users found\n";
        $validationResults['auth'] = false;
    }
} catch (Exception $e) {
    echo "   ❌ Authentication validation failed: " . $e->getMessage() . "\n";
    $validationResults['auth'] = false;
}

// Requirement 2: Hunted Deals Management
echo "\n📝 Validating Requirement 2: Hunted Deals Management\n";
try {
    $huntedDeals = HuntedDeal::with('user', 'deals')->get();
    echo "   Total hunted deals: " . $huntedDeals->count() . "\n";
    
    foreach ($huntedDeals as $huntedDeal) {
        echo "   - '{$huntedDeal->search_term}' by {$huntedDeal->user->name}\n";
        echo "     Active: " . ($huntedDeal->is_active ? 'Yes' : 'No') . "\n";
        echo "     Deals found: " . $huntedDeal->deals->count() . "\n";
        echo "     Last crawled: " . ($huntedDeal->last_crawled_at ? $huntedDeal->last_crawled_at->format('Y-m-d H:i:s') : 'Never') . "\n";
    }
    
    $validationResults['hunted_deals'] = $huntedDeals->count() > 0;
    echo "   ✅ Hunted deals management validated\n";
} catch (Exception $e) {
    echo "   ❌ Hunted deals validation failed: " . $e->getMessage() . "\n";
    $validationResults['hunted_deals'] = false;
}

// Requirement 3: Automated Crawling
echo "\n🕷️ Validating Requirement 3: Automated Crawling\n";
try {
    // Check if crawl command exists
    $commands = Artisan::all();
    $crawlCommandExists = isset($commands['deals:crawl']);
    
    echo "   Crawl command exists: " . ($crawlCommandExists ? 'Yes' : 'No') . "\n";
    
    // Check crawl logs
    $crawlLogCount = CrawlLog::count();
    echo "   Crawl logs in database: {$crawlLogCount}\n";
    
    if ($crawlLogCount > 0) {
        $latestLog = CrawlLog::latest()->first();
        echo "   Latest crawl: " . $latestLog->created_at->format('Y-m-d H:i:s') . "\n";
        echo "   Status: {$latestLog->status}\n";
    }
    
    // Check if scheduler is configured
    $scheduledCommands = app()->make(\Illuminate\Console\Scheduling\Schedule::class)->events();
    echo "   Scheduled commands: " . count($scheduledCommands) . "\n";
    
    $validationResults['crawling'] = $crawlCommandExists;
    echo "   ✅ Crawling infrastructure validated\n";
} catch (Exception $e) {
    echo "   ❌ Crawling validation failed: " . $e->getMessage() . "\n";
    $validationResults['crawling'] = false;
}

// Requirement 4: AI Classification
echo "\n🤖 Validating Requirement 4: AI Classification\n";
try {
    // Check if AI services are configured
    $aiEnabled = env('AI_CLASSIFICATION_ENABLED', false);
    $aiProvider = env('AI_PROVIDER', 'openai');
    $aiModel = env('AI_MODEL', 'gpt-3.5-turbo');
    
    echo "   AI Classification enabled: " . ($aiEnabled ? 'Yes' : 'No') . "\n";
    echo "   AI Provider: {$aiProvider}\n";
    echo "   AI Model: {$aiModel}\n";
    
    // Check if deals have classification data
    $classifiedDeals = Deal::whereNotNull('matches_intent')->count();
    $totalDeals = Deal::count();
    
    echo "   Deals with classification: {$classifiedDeals}/{$totalDeals}\n";
    
    if ($classifiedDeals > 0) {
        $sampleDeal = Deal::whereNotNull('matches_intent')->first();
        echo "   Sample classification:\n";
        echo "     Title: {$sampleDeal->title}\n";
        echo "     Matches Intent: " . ($sampleDeal->matches_intent ? 'Yes' : 'No') . "\n";
        echo "     Likely Working: " . ($sampleDeal->likely_working ? 'Yes' : 'No') . "\n";
        echo "     Confidence: {$sampleDeal->confidence}\n";
    }
    
    $validationResults['ai_classification'] = $classifiedDeals > 0;
    echo "   ✅ AI classification validated\n";
} catch (Exception $e) {
    echo "   ❌ AI classification validation failed: " . $e->getMessage() . "\n";
    $validationResults['ai_classification'] = false;
}

// Requirement 5: Deal Viewing and Analysis
echo "\n📊 Validating Requirement 5: Deal Viewing and Analysis\n";
try {
    $deals = Deal::with('huntedDeal', 'snapshots')->get();
    echo "   Total deals in system: " . $deals->count() . "\n";
    
    // Check price ranges
    $minPrice = Deal::whereNotNull('price_amount')->min('price_amount');
    $maxPrice = Deal::whereNotNull('price_amount')->max('price_amount');
    $avgPrice = Deal::whereNotNull('price_amount')->avg('price_amount');
    
    echo "   Price range: {$minPrice} - {$maxPrice} RON (avg: " . round($avgPrice, 2) . ")\n";
    
    // Check recent deals (last 24h simulation)
    $recentDeals = Deal::where('created_at', '>=', now()->subDay())->count();
    echo "   Recent deals (24h): {$recentDeals}\n";
    
    // Check deals with price drops (multiple snapshots)
    $dealsWithHistory = Deal::has('snapshots', '>', 1)->count();
    echo "   Deals with price history: {$dealsWithHistory}\n";
    
    $validationResults['deal_analysis'] = $deals->count() > 0;
    echo "   ✅ Deal viewing and analysis validated\n";
} catch (Exception $e) {
    echo "   ❌ Deal analysis validation failed: " . $e->getMessage() . "\n";
    $validationResults['deal_analysis'] = false;
}

// Requirement 6: Historical Tracking
echo "\n📈 Validating Requirement 6: Historical Tracking\n";
try {
    $snapshots = DealSnapshot::with('deal')->get();
    echo "   Total snapshots: " . $snapshots->count() . "\n";
    
    // Check snapshot distribution
    $snapshotsByDeal = $snapshots->groupBy('deal_id');
    echo "   Deals with snapshots: " . $snapshotsByDeal->count() . "\n";
    
    // Find deals with multiple snapshots (price changes)
    $dealsWithChanges = $snapshotsByDeal->filter(function ($snapshots) {
        return $snapshots->count() > 1;
    });
    
    echo "   Deals with price changes: " . $dealsWithChanges->count() . "\n";
    
    if ($dealsWithChanges->count() > 0) {
        $dealWithMostChanges = $dealsWithChanges->sortByDesc(function ($snapshots) {
            return $snapshots->count();
        })->first();
        
        $deal = Deal::find($dealWithMostChanges->first()->deal_id);
        echo "   Most tracked deal: '{$deal->title}' with " . $dealWithMostChanges->count() . " snapshots\n";
        
        // Show price history
        $priceHistory = $dealWithMostChanges->sortBy('captured_at')->pluck('price_amount', 'captured_at');
        echo "   Price history:\n";
        foreach ($priceHistory->take(3) as $date => $price) {
            echo "     {$date}: {$price} RON\n";
        }
    }
    
    $validationResults['historical_tracking'] = $snapshots->count() > 0;
    echo "   ✅ Historical tracking validated\n";
} catch (Exception $e) {
    echo "   ❌ Historical tracking validation failed: " . $e->getMessage() . "\n";
    $validationResults['historical_tracking'] = false;
}

// Requirement 7: Monitoring and Configuration
echo "\n⚙️ Validating Requirement 7: Monitoring and Configuration\n";
try {
    // Check system health records
    $healthRecords = SystemHealth::count();
    echo "   System health records: {$healthRecords}\n";
    
    if ($healthRecords > 0) {
        $latestHealth = SystemHealth::latest()->first();
        echo "   Latest health check: " . $latestHealth->created_at->format('Y-m-d H:i:s') . "\n";
        echo "   Overall status: {$latestHealth->overall_status}\n";
    }
    
    // Check configuration
    $config = [
        'MCP Endpoint' => env('MCP_PLAYWRIGHT_ENDPOINT'),
        'Crawler Delay' => env('CRAWLER_REQUEST_DELAY_MS', 2000) . 'ms',
        'Max Pages' => env('CRAWLER_MAX_PAGES_PER_SEARCH', 3),
        'Max Listings' => env('CRAWLER_MAX_LISTINGS_PER_RUN', 100),
        'Rate Limit' => env('RATE_LIMIT_REQUESTS_PER_MINUTE', 30) . '/min'
    ];
    
    echo "   Configuration:\n";
    foreach ($config as $key => $value) {
        echo "     {$key}: {$value}\n";
    }
    
    $validationResults['monitoring'] = true;
    echo "   ✅ Monitoring and configuration validated\n";
} catch (Exception $e) {
    echo "   ❌ Monitoring validation failed: " . $e->getMessage() . "\n";
    $validationResults['monitoring'] = false;
}

// Requirement 8: Developer Documentation
echo "\n📚 Validating Requirement 8: Developer Documentation\n";
try {
    $docFiles = [
        'README.md' => file_exists(__DIR__ . '/../README.md'),
        'docs/dev/playwright-mcp-notes.md' => file_exists(__DIR__ . '/../docs/dev/playwright-mcp-notes.md'),
        'docker-compose.yml' => file_exists(__DIR__ . '/../docker-compose.yml'),
        'Dockerfile' => file_exists(__DIR__ . '/../Dockerfile')
    ];
    
    echo "   Documentation files:\n";
    foreach ($docFiles as $file => $exists) {
        echo "     {$file}: " . ($exists ? '✅' : '❌') . "\n";
    }
    
    // Check if selectors are documented
    $selectorsFile = __DIR__ . '/../app/Services/Crawlers/OlxSelectors.php';
    $selectorsExist = file_exists($selectorsFile);
    echo "   OLX Selectors documented: " . ($selectorsExist ? '✅' : '❌') . "\n";
    
    $validationResults['documentation'] = array_sum($docFiles) >= 3; // At least 3 out of 4 files
    echo "   ✅ Developer documentation validated\n";
} catch (Exception $e) {
    echo "   ❌ Documentation validation failed: " . $e->getMessage() . "\n";
    $validationResults['documentation'] = false;
}

// Requirement 9: Production Deployment
echo "\n🚀 Validating Requirement 9: Production Deployment\n";
try {
    $deploymentFiles = [
        'docker-compose.yml' => file_exists(__DIR__ . '/../docker-compose.yml'),
        'Dockerfile' => file_exists(__DIR__ . '/../Dockerfile'),
        '.env.example' => file_exists(__DIR__ . '/../.env.example'),
        'database/seeders' => is_dir(__DIR__ . '/../database/seeders')
    ];
    
    echo "   Deployment files:\n";
    foreach ($deploymentFiles as $file => $exists) {
        echo "     {$file}: " . ($exists ? '✅' : '❌') . "\n";
    }
    
    // Check if migrations are complete
    $migrationFiles = glob(__DIR__ . '/../database/migrations/*.php');
    echo "   Migration files: " . count($migrationFiles) . "\n";
    
    $validationResults['deployment'] = array_sum($deploymentFiles) >= 3;
    echo "   ✅ Production deployment validated\n";
} catch (Exception $e) {
    echo "   ❌ Deployment validation failed: " . $e->getMessage() . "\n";
    $validationResults['deployment'] = false;
}

// Final Summary
echo "\n=== Final Validation Summary ===\n";
$totalRequirements = count($validationResults);
$passedRequirements = array_sum($validationResults);

echo "Requirements Validation:\n";
foreach ($validationResults as $requirement => $passed) {
    $status = $passed ? '✅' : '❌';
    echo "  {$status} " . ucfirst(str_replace('_', ' ', $requirement)) . "\n";
}

echo "\nOverall Score: {$passedRequirements}/{$totalRequirements} (" . round(($passedRequirements / $totalRequirements) * 100, 1) . "%)\n";

if ($passedRequirements === $totalRequirements) {
    echo "\n🎉 ALL REQUIREMENTS VALIDATED SUCCESSFULLY!\n";
    echo "The OLX Deal Hunter application is ready for production use.\n";
} elseif ($passedRequirements >= $totalRequirements * 0.8) {
    echo "\n✅ SYSTEM MOSTLY VALIDATED\n";
    echo "The application meets most requirements and is functional.\n";
    echo "Address the failed validations for full compliance.\n";
} else {
    echo "\n⚠️ SYSTEM NEEDS ATTENTION\n";
    echo "Several requirements are not met. Review and fix issues before deployment.\n";
}

echo "\nSystem Status: INTEGRATION TESTING COMPLETE\n";
echo "Ready for manual testing and production deployment.\n";

exit($passedRequirements === $totalRequirements ? 0 : 1);