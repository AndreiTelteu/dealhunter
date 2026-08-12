<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'source_url',
        'source_hash',
        'disk',
        'path',
        'position',
        'mime_type',
        'byte_size',
        'downloaded_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
            'downloaded_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
