<?php

use App\Http\Controllers\Admin\FuelPriceSourceController;
use App\Http\Controllers\Admin\OperationalEvidenceController;
use App\Http\Controllers\Admin\RateSettingActivationController;
use App\Http\Controllers\Admin\RateSettingController;
use App\Http\Controllers\Admin\WeeklyFuelPriceActivationController;
use App\Http\Controllers\Admin\WeeklyFuelPriceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardPrototypeController;
use App\Http\Controllers\JobRevisionController;
use App\Http\Controllers\LoadingPracticeQuoteController;
use App\Http\Controllers\QuoteExceptionController;
use App\Http\Controllers\SharedRunController;
use App\Http\Controllers\TransportDayController;
use App\Http\Controllers\TransportEnquiryQuoteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');
    Route::get('/prototype/dashboard', DashboardPrototypeController::class)
        ->name('prototype.dashboard');
    Route::get('/quotes/create', [TransportEnquiryQuoteController::class, 'create'])
        ->name('quotes.create');
    Route::post('/transport-enquiries/resolve', [TransportEnquiryQuoteController::class, 'resolve'])
        ->name('transport-enquiries.resolve');
    Route::get('/transport-enquiries/{transportEnquiry}/route-resolutions/{routeResolution}', [TransportEnquiryQuoteController::class, 'review'])
        ->name('transport-enquiries.route-review');
    Route::post('/transport-enquiries/{transportEnquiry}/route-resolutions/{routeResolution}/accept', [TransportEnquiryQuoteController::class, 'accept'])
        ->name('transport-enquiries.route-accept');
    Route::get('/transport-enquiries/{transportEnquiry}/route-resolutions/{routeResolution}/exceptions', [QuoteExceptionController::class, 'routeException'])
        ->name('transport-enquiries.route-exception');
    Route::post('/transport-enquiries/{transportEnquiry}/route-resolutions/{routeResolution}/retry', [QuoteExceptionController::class, 'retry'])
        ->name('transport-enquiries.route-retry');
    Route::post('/transport-enquiries/{transportEnquiry}/route-resolutions/{routeResolution}/correct', [QuoteExceptionController::class, 'correct'])
        ->name('transport-enquiries.route-correct');
    Route::post('/transport-enquiries/{transportEnquiry}/route-resolutions/{routeResolution}/exceptions/manual-miles', [QuoteExceptionController::class, 'manualFallback'])
        ->name('transport-enquiries.manual-fallback');
    Route::get('/loading-practice-quotes/create', [LoadingPracticeQuoteController::class, 'create'])
        ->name('loading-practice-quotes.create');
    Route::post('/loading-practice-quotes', [LoadingPracticeQuoteController::class, 'store'])
        ->name('loading-practice-quotes.store');
    Route::get('/loading-practice-quotes/{loadingPracticeQuote}', [LoadingPracticeQuoteController::class, 'show'])
        ->name('loading-practice-quotes.show');
    Route::patch('/loading-practice-quotes/{loadingPracticeQuote}', [LoadingPracticeQuoteController::class, 'update'])
        ->name('loading-practice-quotes.update');
    Route::get('/shared-runs/create', [SharedRunController::class, 'create'])
        ->name('shared-runs.create');
    Route::post('/shared-runs', [SharedRunController::class, 'store'])
        ->name('shared-runs.store');
    Route::get('/shared-runs/{sharedRun}', [SharedRunController::class, 'show'])
        ->name('shared-runs.show');
    Route::patch('/shared-runs/{sharedRun}', [SharedRunController::class, 'update'])
        ->name('shared-runs.update');
    Route::get('/admin/weekly-fuel-prices', [WeeklyFuelPriceController::class, 'index'])
        ->name('admin.weekly-fuel-prices.index');
    Route::post('/admin/weekly-fuel-prices', [WeeklyFuelPriceController::class, 'store'])
        ->name('admin.weekly-fuel-prices.store');
    Route::post('/admin/weekly-fuel-prices/sources', [FuelPriceSourceController::class, 'store'])
        ->name('admin.fuel-price-sources.store');
    Route::post('/admin/weekly-fuel-prices/{weeklyFuelPrice}/activate', WeeklyFuelPriceActivationController::class)
        ->name('admin.weekly-fuel-prices.activate');
    Route::get('/admin/rate-settings', [RateSettingController::class, 'index'])
        ->name('admin.rate-settings.index');
    Route::get('/admin/operational-evidence', OperationalEvidenceController::class)
        ->name('admin.operational-evidence.index');
    Route::post('/admin/rate-settings', [RateSettingController::class, 'store'])
        ->name('admin.rate-settings.store');
    Route::post('/admin/rate-settings/{rateSetting}/activate', RateSettingActivationController::class)
        ->name('admin.rate-settings.activate');
    Route::get('/jobs/{job}/revisions/{revision}', [JobRevisionController::class, 'show'])
        ->name('jobs.revisions.show');
    Route::get('/jobs/{job}/revisions/{revision}/issued', [JobRevisionController::class, 'issued'])
        ->name('jobs.revisions.issued');
    Route::patch('/jobs/{job}/revisions/{revision}', [JobRevisionController::class, 'update'])
        ->name('jobs.revisions.update');
    Route::post('/jobs/{job}/revisions/{revision}/fuel-price', [JobRevisionController::class, 'selectFuelPrice'])
        ->name('jobs.revisions.fuel-price');
    Route::get('/jobs/{job}/revisions/{revision}/exceptions', [QuoteExceptionController::class, 'revisionException'])
        ->name('jobs.revisions.exceptions');
    Route::post('/jobs/{job}/revisions/{revision}/exceptions/route-legs', [QuoteExceptionController::class, 'overrideRouteLegs'])
        ->name('jobs.revisions.route-leg-overrides');
    Route::post('/jobs/{job}/revisions/{revision}/exceptions/final-total', [QuoteExceptionController::class, 'overrideFinalTotal'])
        ->name('jobs.revisions.final-total-override');
    Route::post('/jobs/{job}/revisions/{revision}/issue', [JobRevisionController::class, 'issue'])
        ->name('jobs.revisions.issue');
    Route::post('/jobs/{job}/revisions/{revision}/pending', [JobRevisionController::class, 'markPending'])
        ->name('jobs.revisions.pending');
    Route::post('/jobs/{job}/revisions/{revision}/draft', [JobRevisionController::class, 'returnToDraft'])
        ->name('jobs.revisions.draft');
    Route::post('/jobs/{job}/revisions/{revision}/book', [JobRevisionController::class, 'book'])
        ->name('jobs.revisions.book');
    Route::post('/jobs/{job}/revisions/{revision}/complete', [JobRevisionController::class, 'complete'])
        ->name('jobs.revisions.complete');
    Route::get('/transport-days', [TransportDayController::class, 'index'])
        ->name('transport-days.index');
    Route::get('/transport-days/create', [TransportDayController::class, 'create'])
        ->name('transport-days.create');
    Route::post('/transport-days', [TransportDayController::class, 'store'])
        ->name('transport-days.store');
    Route::get('/transport-days/{transportDay}', [TransportDayController::class, 'show'])
        ->name('transport-days.show');
    Route::patch('/transport-days/{transportDay}', [TransportDayController::class, 'update'])
        ->name('transport-days.update');
    Route::post('/transport-days/{transportDay}/jobs', [TransportDayController::class, 'addJob'])
        ->name('transport-days.jobs.add');
    Route::post('/transport-days/{transportDay}/jobs/{job}/remove', [TransportDayController::class, 'removeJob'])
        ->name('transport-days.jobs.remove');
    Route::post('/transport-days/{transportDay}/jobs/{job}/move-up', [TransportDayController::class, 'moveJobUp'])
        ->name('transport-days.jobs.move-up');
    Route::post('/transport-days/{transportDay}/jobs/{job}/move-down', [TransportDayController::class, 'moveJobDown'])
        ->name('transport-days.jobs.move-down');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
