<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RouteResolution extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'operator_action_required' => 'boolean',
            'pricing_eligible' => 'boolean',
            'raw_input_snapshot' => 'array',
            'request_context' => 'array',
            'normalisation_metadata' => 'array',
            'provider_metadata' => 'array',
            'warnings' => 'array',
            'failure_detail' => 'array',
            'resolved_at' => 'datetime',
            'attempted_at' => 'datetime',
            'request_started_at' => 'datetime',
            'response_received_at' => 'datetime',
            'latency_milliseconds' => 'integer',
        ];
    }

    public function previousRouteResolution(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_route_resolution_id');
    }

    public function transportEnquiry(): BelongsTo
    {
        return $this->belongsTo(TransportEnquiry::class);
    }

    public function legs(): HasMany
    {
        return $this->hasMany(RouteResolutionLeg::class)->orderBy('sequence');
    }

    public function reviewDecisions(): HasMany
    {
        return $this->hasMany(RouteResolutionReviewDecision::class)->orderBy('recorded_at');
    }
}
