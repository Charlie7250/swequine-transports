<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingPracticeQuote extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'travel_miles' => 'integer',
            'package_price' => 'decimal:2',
            'on_site_hours' => 'decimal:2',
            'on_site_hourly_rate' => 'decimal:2',
            'handling_livery_quantity' => 'integer',
            'handling_livery_rate' => 'decimal:2',
            'is_poa' => 'boolean',
            'engine_total' => 'decimal:2',
            'final_total' => 'decimal:2',
            'calculation_explanation' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function rateSetting(): BelongsTo
    {
        return $this->belongsTo(RateSetting::class);
    }
}
