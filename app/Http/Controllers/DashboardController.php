<?php

namespace App\Http\Controllers;

use App\Services\Reporting\OperatorDashboardBuilder;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly OperatorDashboardBuilder $dashboardBuilder,
    ) {}

    public function __invoke(): View
    {
        $userName = trim((string) auth()->user()?->name);

        return view('dashboard', [
            'dashboard' => $this->dashboardBuilder->build(),
            'greetingName' => $userName === '' ? null : Str::before($userName, ' '),
        ]);
    }
}
