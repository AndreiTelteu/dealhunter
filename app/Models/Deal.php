<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Deal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hunted_deal_id',
        'external_id',
        'url',
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
        'intent_score',
        'likely_working',
        'confidence',
        'last_seen_at',
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
            'intent_score' => 'integer',
            'likely_working' => 'boolean',
            'image_urls' => 'array',
            'posted_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get the hunted deal that owns the deal.
     */
    public function huntedDeal(): BelongsTo
    {
        return $this->belongsTo(HuntedDeal::class);
    }

    /**
     * Get the snapshots for the deal.
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(DealSnapshot::class)->orderBy('captured_at', 'desc');
    }

    /**
     * Get the latest snapshot for the deal.
     */
    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(DealSnapshot::class)->latestOfMany('captured_at');
    }

    /**
     * Get the users who favorited the deal.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * Add a boolean is_favorite attribute for the given user.
     */
    public function scopeWithFavoriteState(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->withExists([
            'favoritedBy as is_favorite' => fn ($relation) => $relation->where('favorites.user_id', $userId),
        ]);
    }
}
