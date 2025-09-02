<?php

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use Illuminate\Console\Command;

class SystemHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:health-check 
                            {--component= : Check specific component only (database, mcp, crawler, ai)}
                            {--alert-on-failure : Exit with error code if any component is unhealthy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check system health for all components or a specific component';

    /**
     * Execute the console command.
     */
    public function handle(SystemHealthService $healthService): int
    {
        $specificComponent = $this->option('component');
        $alertOnFailure = $this->option('alert-on-failure');
        
        $this->info('Running system health check...');
        
        try {
            if ($specificComponent) {
                // Check specific component
                $result = match ($specificComponent) {
                    'database' => $healthService->checkDatabase(),
                    'mcp' => $healthService->checkMcpConnection(),
                    'crawler' => $healthService->checkCrawlerHealth(),
                    'ai' => $healthService->checkAiService(),
                    default => throw new \InvalidArgumentException("Unknown component: {$specificComponent}")
                };
                
                $this->displayComponentResult($specificComponent, $result);
                
                if ($alertOnFailure && !$result->isHealthy()) {
                    return 1;
                }
                
            } else {
                // Check all components
                $results = $healthService->checkAllComponents();
                $overallHealth = $healthService->getOverallHealth();
                
                $this->info('');
                $this->info('=== System Health Check Results ===');
                
                foreach ($results as $component => $result) {
                    $this->displayComponentResult($component, $result);
                }
                
                $this->info('');
                $this->info('=== Overall Health Summary ===');
                
                $summary = $overallHealth['summary'];
                $tableData = [
                    ['Healthy', $summary['healthy']],
                    ['Warning', $summary['warning']],
                    ['Critical', $summary['critical']],
                    ['Unknown', $summary['unknown']],
                    ['Total', $summary['total']],
                ];
                
                $this->table(['Status', 'Count'], $tableData);
                
                $overallStatus = $overallHealth['overall_status'];
                
                if ($overallStatus === 'healthy') {
                    $this->info('✅ All systems are healthy!');
                } elseif ($overallStatus === 'warning') {
                    $this->warn('⚠️  Some systems have warnings');
                } elseif ($overallStatus === 'critical') {
                    $this->error('❌ Critical issues detected');
                } else {
                    $this->warn('❓ System status unknown');
                }
                
                if ($alertOnFailure && $overallStatus !== 'healthy') {
                    return 1;
                }
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Health check failed: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Display the result for a single component
     */
    private function displayComponentResult(string $component, $result): void
    {
        $status = $result->status;
        $message = $result->message;
        $responseTime = $result->response_time_ms;
        
        $statusIcon = match ($status) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'critical' => '❌',
            default => '❓',
        };
        
        $componentName = ucfirst($component);
        $timeInfo = $responseTime ? " ({$responseTime}ms)" : '';
        
        $this->line("{$statusIcon} {$componentName}: {$message}{$timeInfo}");
        
        // Show additional details if verbose
        if ($this->getOutput()->isVerbose() && $result->details) {
            foreach ($result->details as $key => $value) {
                $key = ucfirst(str_replace('_', ' ', $key));
                $value = is_array($value) ? json_encode($value) : $value;
                $this->line("   {$key}: {$value}");
            }
        }
    }
}
