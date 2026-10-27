<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortLink extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'target_url',
        'utm_source',
        'utm_medium',
        'webhook_url',
        'expires_at',
        'clicks_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'clicks_count' => 'integer',
        ];
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(LinkClick::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
