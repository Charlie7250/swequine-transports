<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteExceptionAudit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function jobRevision(): BelongsTo
    {
        return $this->belongsTo(JobRevision::class);
    }

    public function sourceQuoteExceptionAudit(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_quote_exception_audit_id');
    }

    public function routeLeg(): BelongsTo
    {
        return $this->belongsTo(RouteLeg::class);
    }

    public function routeResolutionLeg(): BelongsTo
    {
        return $this->belongsTo(RouteResolutionLeg::class);
    }
}
