<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class CrawlLog extends Model
{
    protected $fillable = [
        'type',
        'status',
        'started_at',
        'completed_at',
        'duration_ms',
        'hunted_deals_processed',
        'hunted_deals_failed',
        'total_listings_found',
        'new_deals_created',
        'deals_updated',
        'snapshots_created',
        'total_errors',
        'success_rate',
        'listings_per_second',
        'configuration',
        'errors',
        'notes',
        'triggered_by',
        'user_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'configuration' => 'array',
        'errors' => 'array',
        'success_rate' => 'decimal:2',
        'listings_per_second' => 'decimal:2',
    ];

    /**
     * Get the user who triggered this crawl (if manual)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted duration
     */
    protected function formattedDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->duration_ms) {
                    return 'N/A';
                }
                
                if ($this->duration_ms < 1000) {
                    return $this->duration_ms . 'ms';
                }
                
                $seconds = round($this->duration_ms / 1000, 1);
                if ($seconds < 60) {
                    return $seconds . 's';
                }
                
                $minutes = floor($seconds / 60);
                $remainingSeconds = $seconds % 60;
                return $minutes . 'm ' . round($remainingSeconds, 1) . 's';
            }
        );
    }

    /**
     * Check if crawl was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->total_errors === 0;
    }

    /**
     * Check if crawl had partial success
     */
    public function hasPartialSuccess(): bool
    {
        return $this->status === 'completed' && $this->hunted_deals_processed > $this->hunted_deals_failed;
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'completed' => $this->total_errors === 0 ? 'green' : 'yellow',
            'failed' => 'red',
            'started' => 'blue',
            'partial' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope for successful crawls
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed')->where('total_errors', 0);
    }

    /**
     * Scope for failed crawls
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
