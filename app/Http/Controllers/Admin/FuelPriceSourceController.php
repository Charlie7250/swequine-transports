<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFuelPriceSourceRequest;
use App\Models\FuelPriceSource;
use Illuminate\Http\RedirectResponse;

class FuelPriceSourceController extends Controller
{
    public function store(StoreFuelPriceSourceRequest $request): RedirectResponse
    {
        FuelPriceSource::query()->create($request->only('key', 'display_name'));

        return redirect()
            ->route('admin.weekly-fuel-prices.index')
            ->with('status', 'Fuel source saved.');
    }
}
