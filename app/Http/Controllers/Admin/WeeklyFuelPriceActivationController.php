<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeeklyFuelPrice;
use App\Services\Pricing\WeeklyFuelPriceManager;
use Illuminate\Http\RedirectResponse;

class WeeklyFuelPriceActivationController extends Controller
{
    public function __construct(private readonly WeeklyFuelPriceManager $manager) {}

    public function __invoke(WeeklyFuelPrice $weeklyFuelPrice): RedirectResponse
    {
        $this->manager->activate($weeklyFuelPrice);

        return redirect()
            ->route('admin.weekly-fuel-prices.index')
            ->with('status', 'Weekly fuel price is now the active fuel input.');
    }
}
