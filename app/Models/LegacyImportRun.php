<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyImportRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'target_identity' => 'array',
            'counts' => 'array',
            'identifier_mappings' => 'array',
            'verification_results' => 'array',
            'is_dry_run' => 'boolean',
            'was_maintenance_active' => 'boolean',
            'started_at' => 'datetime',
            'committed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
