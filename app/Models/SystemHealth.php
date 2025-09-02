<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SystemHealth extends Model
{
    protected $fillable = [
        'component',
        'status',
        'message',
        'response_time_ms',
        'details',
        'checked_at',
    ];

    protected $casts = [
        'details' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Get status badge color for UI
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            'unknown' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if status is healthy
     */
    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    /**
     * Check if status needs attention
     */
    public function needsAttention(): bool
    {
        return in_array($this->status, ['warning', 'critical']);
    }

    /**
     * Scope for recent health checks
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('checked_at', '>=', Carbon::now()->subMinutes($minutes));
    }

    /**
     * Scope for specific component
     */
    public function scopeForComponent($query, string $component)
    {
        return $query->where('component', $component);
    }

    /**
     * Scope for unhealthy statuses
     */
    public function scopeUnhealthy($query)
    {
        return $query->whereIn('status', ['warning', 'critical']);
    }

    /**
     * Get latest health check for each component
     */
    public static function getLatestForAllComponents(): array
    {
        $components = ['database', 'mcp', 'crawler', 'ai'];
        $results = [];
        
        foreach ($components as $component) {
            $latest = static::where('component', $component)
                ->orderBy('checked_at', 'desc')
                ->first();
                
            $results[$component] = $latest;
        }
        
        return $results;
    }
}
