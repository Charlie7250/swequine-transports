<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\RateSetting;
use App\Models\WeeklyFuelPrice;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'jobCount' => Job::count(),
            'activeFuelPrice' => WeeklyFuelPrice::query()->where('is_active', true)->first(),
            'activeRateSetting' => RateSetting::query()->where('is_active', true)->first(),
        ]);
    }
}
