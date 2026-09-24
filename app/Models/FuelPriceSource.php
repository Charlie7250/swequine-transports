<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FuelPriceSource extends Model
{
    use HasFactory;

    protected $guarded = [];

    private static ?array $labelMap = null;

    public static function labelFor(string $key): string
    {
        self::$labelMap ??= self::query()->pluck('display_name', 'key')->all();

        return self::$labelMap[$key] ?? Str::headline($key);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::$labelMap = null);
        static::deleted(fn () => self::$labelMap = null);
    }
}
