<?php

namespace App\Services\Pricing;

use App\Models\RateSetting;
use Illuminate\Support\Facades\DB;

class RateSettingManager
{
    public function create(array $attributes): RateSetting
    {
        return DB::transaction(function () use ($attributes): RateSetting {
            $activateNow = (bool) ($attributes['activate_now'] ?? false);
            unset($attributes['activate_now']);

            $rateSetting = RateSetting::query()->create(array_merge($attributes, [
                'is_active' => false,
            ]));

            if (! $activateNow) {
                return $rateSetting;
            }

            return $this->activate($rateSetting);
        });
    }

    public function activate(RateSetting $rateSetting): RateSetting
    {
        return DB::transaction(function () use ($rateSetting): RateSetting {
            RateSetting::query()
                ->where('is_active', true)
                ->whereKeyNot($rateSetting->getKey())
                ->update(['is_active' => false]);

            $rateSetting->forceFill([
                'is_active' => true,
            ])->save();

            return $rateSetting->refresh();
        });
    }
}
