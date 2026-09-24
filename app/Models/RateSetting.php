<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RateSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'miles_per_gallon' => 'decimal:4',
            'litres_per_gallon' => 'decimal:4',
            'maintenance_per_mile' => 'decimal:6',
            'unloaded_add_on_per_mile' => 'decimal:6',
            'loaded_add_on_per_mile' => 'decimal:6',
            'one_horse_multiplier' => 'decimal:6',
            'shared_load_percentage' => 'decimal:6',
            'two_horse_multiplier' => 'decimal:6',
            'loading_practice_within_15_miles_price' => 'decimal:2',
            'loading_practice_within_25_miles_price' => 'decimal:2',
            'loading_practice_within_50_miles_price' => 'decimal:2',
            'loading_practice_on_site_hourly_rate' => 'decimal:2',
            'loading_practice_livery_day_rate' => 'decimal:2',
            'loading_practice_livery_week_rate' => 'decimal:2',
            'loading_practice_livery_fortnight_rate' => 'decimal:2',
        ];
    }

    public function jobRevisions(): HasMany
    {
        return $this->hasMany(JobRevision::class);
    }

    public function loadingPracticeQuotes(): HasMany
    {
        return $this->hasMany(LoadingPracticeQuote::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
