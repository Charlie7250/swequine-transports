<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveLoadingPracticeQuoteRequest;
use App\Models\LoadingPracticeQuote;
use App\Services\LoadingPractice\LoadingPracticeQuoteManager;
use App\Services\Pricing\LoadingPracticePricingContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoadingPracticeQuoteController extends Controller
{
    public function __construct(
        private readonly LoadingPracticePricingContextResolver $pricingContextResolver,
        private readonly LoadingPracticeQuoteManager $quoteManager,
    ) {}

    public function create(): View
    {
        return view('loading-practice-quotes.create', [
            'pricingContext' => $this->pricingContextResolver->resolveForDraftWorkspace(),
        ]);
    }

    public function store(SaveLoadingPracticeQuoteRequest $request): RedirectResponse
    {
        $quote = $this->quoteManager->create($request->validated());

        return redirect()
            ->route('loading-practice-quotes.show', $quote)
            ->with('status', 'Loading-practice quote draft saved.');
    }

    public function show(LoadingPracticeQuote $loadingPracticeQuote): View
    {
        $loadingPracticeQuote->load(['customer', 'rateSetting']);

        return view('loading-practice-quotes.show', [
            'quote' => $loadingPracticeQuote,
            'pricingContext' => $this->pricingContextResolver->resolve($loadingPracticeQuote),
        ]);
    }

    public function update(LoadingPracticeQuote $loadingPracticeQuote, SaveLoadingPracticeQuoteRequest $request): RedirectResponse
    {
        $this->quoteManager->update($loadingPracticeQuote, $request->validated());

        return redirect()
            ->route('loading-practice-quotes.show', $loadingPracticeQuote)
            ->with('status', 'Loading-practice quote updated.');
    }
}
