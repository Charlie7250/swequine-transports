<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeeklyFuelPriceRequest;
use App\Models\FuelPriceSource;
use App\Models\WeeklyFuelPrice;
use App\Services\Pricing\WeeklyFuelPriceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WeeklyFuelPriceController extends Controller
{
    public function __construct(private readonly WeeklyFuelPriceManager $manager) {}

    public function index(): View
    {
        return view('admin.weekly-fuel-prices.index', [
            'activeFuelPrice' => WeeklyFuelPrice::query()
                ->active()
                ->orderByDesc('activated_at')
                ->orderByDesc('id')
                ->first(),
            'weeklyFuelPrices' => WeeklyFuelPrice::query()
                ->orderByDesc('week_commencing')
                ->orderByDesc('id')
                ->get(),
            'sources' => FuelPriceSource::query()
                ->orderBy('display_name')
                ->get(),
        ]);
    }

    public function store(StoreWeeklyFuelPriceRequest $request): RedirectResponse
    {
        $this->manager->create($request->validated());

        return redirect()
            ->route('admin.weekly-fuel-prices.index')
            ->with('status', 'Weekly fuel price saved.');
    }
}
