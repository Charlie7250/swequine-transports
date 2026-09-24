<?php

namespace App\Services\Pricing;

use App\Models\WeeklyFuelPrice;
use Illuminate\Support\Facades\DB;

class WeeklyFuelPriceManager
{
    public function create(array $attributes): WeeklyFuelPrice
    {
        return DB::transaction(function () use ($attributes): WeeklyFuelPrice {
            $activateNow = (bool) ($attributes['activate_now'] ?? false);
            unset($attributes['activate_now']);

            $weeklyFuelPrice = WeeklyFuelPrice::query()->create(array_merge($attributes, [
                'is_active' => false,
                'activated_at' => null,
            ]));

            if (! $activateNow) {
                return $weeklyFuelPrice;
            }

            return $this->activate($weeklyFuelPrice);
        });
    }

    public function activate(WeeklyFuelPrice $weeklyFuelPrice): WeeklyFuelPrice
    {
        return DB::transaction(function () use ($weeklyFuelPrice): WeeklyFuelPrice {
            WeeklyFuelPrice::query()
                ->where('is_active', true)
                ->whereKeyNot($weeklyFuelPrice->getKey())
                ->update(['is_active' => false]);

            $weeklyFuelPrice->forceFill([
                'is_active' => true,
                'activated_at' => now(),
            ])->save();

            return $weeklyFuelPrice->refresh();
        });
    }
}
