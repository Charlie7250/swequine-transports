<?php

namespace App\Models;

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
            'two_horse_multiplier' => 'decimal:6',
        ];
    }

    public function jobRevisions(): HasMany
    {
        return $this->hasMany(JobRevision::class);
    }
}
