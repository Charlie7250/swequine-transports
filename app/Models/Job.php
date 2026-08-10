<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'booked_at' => 'datetime',
            'completed_at' => 'datetime',
            'lost_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(JobRevision::class);
    }

    public function currentWorkingRevision(): BelongsTo
    {
        return $this->belongsTo(JobRevision::class, 'current_working_revision_id');
    }

    public function issuedRevision(): BelongsTo
    {
        return $this->belongsTo(JobRevision::class, 'issued_revision_id');
    }

    public function acceptedRevision(): BelongsTo
    {
        return $this->belongsTo(JobRevision::class, 'accepted_revision_id');
    }
}
