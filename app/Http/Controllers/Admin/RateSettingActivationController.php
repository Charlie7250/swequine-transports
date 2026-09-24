<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RateSetting;
use App\Services\Pricing\RateSettingManager;
use Illuminate\Http\RedirectResponse;

class RateSettingActivationController extends Controller
{
    public function __construct(private readonly RateSettingManager $manager) {}

    public function __invoke(RateSetting $rateSetting): RedirectResponse
    {
        $this->manager->activate($rateSetting);

        return redirect()
            ->route('admin.rate-settings.index')
            ->with('status', 'Rate setting is now active for new pricing.');
    }
}
