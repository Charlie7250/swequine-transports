<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRateSettingRequest;
use App\Models\RateSetting;
use App\Services\Pricing\RateSettingManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RateSettingController extends Controller
{
    public function __construct(private readonly RateSettingManager $manager) {}

    public function index(): View
    {
        return view('admin.rate-settings.index', [
            'activeRateSetting' => RateSetting::query()
                ->active()
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first(),
            'rateSettings' => RateSetting::query()
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(StoreRateSettingRequest $request): RedirectResponse
    {
        $this->manager->create($request->validated());

        return redirect()
            ->route('admin.rate-settings.index')
            ->with('status', 'Rate setting saved.');
    }
}
