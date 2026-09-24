<?php

namespace App\Services\Pricing;

use App\Models\LoadingPracticeQuote;
use App\Models\RateSetting;

class LoadingPracticePricingContextResolver
{
    public function resolveForDraftWorkspace(): array
    {
        return [
            'rate_setting' => $this->activeRateSetting(),
            'rate_setting_is_stored' => false,
            'rate_setting_origin' => 'New loading-practice quotes will store this active rate setting record when they are first priced.',
        ];
    }

    public function resolve(LoadingPracticeQuote $quote): array
    {
        $storedRateSetting = $quote->rateSetting;

        if ($storedRateSetting !== null) {
            return [
                'rate_setting' => $storedRateSetting,
                'rate_setting_is_stored' => true,
                'rate_setting_origin' => 'Pricing remains tied to the stored rate setting that this loading-practice quote first used.',
            ];
        }

        return [
            'rate_setting' => null,
            'rate_setting_is_stored' => true,
            'rate_setting_origin' => 'This loading-practice quote is missing its stored rate setting and cannot be repriced until that record is restored.',
        ];
    }

    public function resolveRateSettingForCreate(): ?RateSetting
    {
        return $this->activeRateSetting();
    }

    public function resolveRateSettingForUpdate(LoadingPracticeQuote $quote): ?RateSetting
    {
        return $quote->rateSetting;
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
