<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobRevision extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'calculation_explanation' => 'array',
            'engine_total' => 'decimal:2',
            'final_total' => 'decimal:2',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function weeklyFuelPrice(): BelongsTo
    {
        return $this->belongsTo(WeeklyFuelPrice::class);
    }

    public function rateSetting(): BelongsTo
    {
        return $this->belongsTo(RateSetting::class);
    }

    public function routeLegs(): HasMany
    {
        return $this->hasMany(RouteLeg::class);
    }

    public function sharedRunAllocations(): HasMany
    {
        return $this->hasMany(SharedRunAllocation::class);
    }
}
