<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteLeg extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rate_per_mile' => 'decimal:6',
            'amount' => 'decimal:2',
        ];
    }

    public function jobRevision(): BelongsTo
    {
        return $this->belongsTo(JobRevision::class);
    }
}
