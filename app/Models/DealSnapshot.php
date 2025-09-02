<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealSnapshot extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'deal_id',
        'title',
        'price_amount',
        'price_currency',
        'price_raw',
        'description',
        'image_urls',
        'location',
        'seller_name',
        'seller_url',
        'posted_at',
        'matches_intent',
        'likely_working',
        'confidence',
        'captured_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'confidence' => 'decimal:2',
            'matches_intent' => 'boolean',
            'likely_working' => 'boolean',
            'image_urls' => 'array',
            'posted_at' => 'datetime',
            'captured_at' => 'datetime',
        ];
    }

    /**
     * Get the deal that owns the snapshot.
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
