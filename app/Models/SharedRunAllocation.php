<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedRunAllocation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'allocation_explanation' => 'array',
            'total_charge' => 'decimal:2',
        ];
    }

    public function sharedRun(): BelongsTo
    {
        return $this->belongsTo(SharedRun::class);
    }

    public function jobRevision(): BelongsTo
    {
        return $this->belongsTo(JobRevision::class);
    }
}
