<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyFuelPrice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'week_commencing' => 'date',
            'activated_at' => 'datetime',
            'is_active' => 'boolean',
            'price_per_litre_inc_vat' => 'decimal:4',
        ];
    }

    public function jobRevisions(): HasMany
    {
        return $this->hasMany(JobRevision::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
