<?php

namespace App\Services\Pricing;

use App\Models\JobRevision;
use App\Models\RateSetting;
use App\Models\WeeklyFuelPrice;

class JobRevisionPricingContextResolver
{
    public function resolveForDraftWorkspace(): array
    {
        return [
            'weekly_fuel_price' => $this->activeWeeklyFuelPrice(),
            'weekly_fuel_price_origin' => 'Current active default',
            'weekly_fuel_price_is_stored' => false,
            'rate_setting' => $this->activeRateSetting(),
            'rate_setting_origin' => 'Current active default',
            'rate_setting_is_stored' => false,
        ];
    }

    public function resolve(JobRevision $revision): array
    {
        $revision->loadMissing('weeklyFuelPrice', 'rateSetting');

        return [
            'weekly_fuel_price' => $revision->weeklyFuelPrice ?? $this->activeWeeklyFuelPrice(),
            'weekly_fuel_price_origin' => $revision->weeklyFuelPrice !== null ? 'Stored on this revision' : 'Current active default',
            'weekly_fuel_price_is_stored' => $revision->weeklyFuelPrice !== null,
            'rate_setting' => $revision->rateSetting ?? $this->activeRateSetting(),
            'rate_setting_origin' => $revision->rateSetting !== null ? 'Stored on this revision' : 'Current active default',
            'rate_setting_is_stored' => $revision->rateSetting !== null,
        ];
    }

    private function activeWeeklyFuelPrice(): ?WeeklyFuelPrice
    {
        return WeeklyFuelPrice::query()
            ->active()
            ->orderByDesc('activated_at')
            ->orderByDesc('id')
            ->first();
    }

    private function activeRateSetting(): ?RateSetting
    {
        return RateSetting::query()
            ->active()
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }
}
