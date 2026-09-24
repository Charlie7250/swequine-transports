<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportEnquiry extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'horse_count' => 'integer',
            'requested_date' => 'date',
            'date_to_be_arranged' => 'boolean',
            'special_constraints_acknowledged' => 'boolean',
        ];
    }

    public function quoteJob(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'quote_job_id');
    }

    public function routeResolutions(): HasMany
    {
        return $this->hasMany(RouteResolution::class)->orderBy('id');
    }
}
