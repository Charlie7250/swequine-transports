<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteResolutionLeg extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'distance_metres' => 'integer',
            'quoted_miles' => 'integer',
            'duration_seconds' => 'integer',
            'resolved_origin_metadata' => 'array',
            'resolved_destination_metadata' => 'array',
            'warnings' => 'array',
            'failure_detail' => 'array',
        ];
    }

    public function routeResolution(): BelongsTo
    {
        return $this->belongsTo(RouteResolution::class);
    }
}
