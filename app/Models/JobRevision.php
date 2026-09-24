<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
            'issued_at' => 'datetime',
            'issued_evidence' => 'array',
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

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function routeResolution(): BelongsTo
    {
        return $this->belongsTo(RouteResolution::class);
    }

    public function routeLegs(): HasMany
    {
        return $this->hasMany(RouteLeg::class);
    }

    public function quoteExceptionAudits(): HasMany
    {
        return $this->hasMany(QuoteExceptionAudit::class)->orderBy('recorded_at');
    }

    public function sharedRunAllocations(): HasMany
    {
        return $this->hasMany(SharedRunAllocation::class);
    }

    public function sharedRunAllocation(): HasOne
    {
        return $this->hasOne(SharedRunAllocation::class);
    }
}
