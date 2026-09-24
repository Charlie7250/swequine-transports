<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportDay extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'run_date' => 'date',
        ];
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class)->orderBy('transport_day_sequence');
    }
}
