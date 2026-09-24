<?php

namespace App\Http\Controllers;

use App\Support\DashboardPrototypeFixture;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardPrototypeController extends Controller
{
    public function __invoke(): View
    {
        $userName = trim((string) auth()->user()?->name);

        return view('dashboard-prototype.index', [
            'dashboard' => DashboardPrototypeFixture::make(),
            'greetingName' => $userName === '' ? null : Str::before($userName, ' '),
        ]);
    }
}
