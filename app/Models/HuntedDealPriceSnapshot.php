<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntedDealPriceSnapshot extends Model
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
        'hunted_deal_id',
        'average_price',
        'min_price',
        'max_price',
        'deals_count',
        'price_currency',
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
            'average_price' => 'decimal:2',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'deals_count' => 'integer',
            'captured_at' => 'datetime',
        ];
    }

    /**
     * Get the hunted deal that owns the price snapshot.
     */
    public function huntedDeal(): BelongsTo
    {
        return $this->belongsTo(HuntedDeal::class);
    }
}
