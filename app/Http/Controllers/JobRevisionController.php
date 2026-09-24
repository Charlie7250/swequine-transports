<?php

namespace App\Http\Controllers;

use App\Http\Requests\IssueJobRevisionRequest;
use App\Http\Requests\SaveQuoteWorkspaceRequest;
use App\Http\Requests\SelectRevisionFuelPriceRequest;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\WeeklyFuelPrice;
use App\Services\Pricing\JobRevisionPricingContextResolver;
use App\Services\Quotes\IssuedQuoteChecklist;
use App\Services\Quotes\QuoteWorkspaceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JobRevisionController extends Controller
{
    public function __construct(
        private readonly JobRevisionPricingContextResolver $contextResolver,
        private readonly IssuedQuoteChecklist $issuedQuoteChecklist,
        private readonly QuoteWorkspaceManager $workspaceManager,
    ) {}

    public function create(): View
    {
        return view('job-revisions.create', [
            'pricingContext' => $this->contextResolver->resolveForDraftWorkspace(),
        ]);
    }

    public function store(SaveQuoteWorkspaceRequest $request): RedirectResponse
    {
        $revision = $this->workspaceManager->create($request->validated());

        return redirect()
            ->route('jobs.revisions.show', ['job' => $revision->job_id, 'revision' => $revision->id])
            ->with('status', 'Quote draft saved.');
    }

    public function show(Job $job, JobRevision $revision): View
    {
        $this->ensureRevisionBelongsToJob($job, $revision);

        $job->load([
            'customer',
            'currentWorkingRevision',
            'issuedRevision',
            'acceptedRevision',
            'revisions' => fn ($query) => $query->orderByDesc('revision_number'),
        ]);
        $revision->load([
            'job.customer',
            'routeLegs' => fn ($query) => $query->orderBy('sequence'),
            'weeklyFuelPrice',
            'rateSetting',
            'routeResolution',
            'routeResolution.legs',
            'quoteExceptionAudits.actor',
            'sharedRunAllocation.sharedRun',
        ]);

        return view('job-revisions.show', [
            'job' => $job,
            'revision' => $revision,
            'pricingContext' => $this->contextResolver->resolve($revision),
            'issueChecklist' => $this->issuedQuoteChecklist->for($revision),
            'weeklyFuelPrices' => WeeklyFuelPrice::query()
                ->orderByDesc('week_commencing')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function update(Job $job, JobRevision $revision, SaveQuoteWorkspaceRequest $request): RedirectResponse
    {
        $this->ensureRevisionBelongsToJob($job, $revision);
        $updatedRevision = $this->workspaceManager->update(
            $revision,
            $request->validated(),
            $request->user()->id,
        );

        return redirect()
            ->route('jobs.revisions.show', ['job' => $job, 'revision' => $updatedRevision])
            ->with('status', 'Quote workspace updated.');
    }

    public function issue(Job $job, JobRevision $revision, IssueJobRevisionRequest $request): RedirectResponse
    {
        $this->ensureRevisionBelongsToJob($job, $revision);
        $this->workspaceManager->issue($revision, $request->user()->id);

        return redirect()
            ->route('jobs.revisions.issued', ['job' => $job, 'revision' => $revision])
            ->with('status', 'Quote issued.');
    }

    public function selectFuelPrice(
        Job $job,
        JobRevision $revision,
        SelectRevisionFuelPriceRequest $request,
    ): RedirectResponse {
        $this->ensureRevisionBelongsToJob($job, $revision);
        $updatedRevision = $this->workspaceManager->selectFuelPrice(
            $revision,
            (int) $request->validated('weekly_fuel_price_id'),
        );

        return redirect()
            ->route('jobs.revisions.show', ['job' => $job, 'revision' => $updatedRevision])
            ->with('status', 'Corrected fuel price applied to a new draft revision.');
    }

    public function issued(Job $job, JobRevision $revision): View
    {
        $this->ensureRevisionBelongsToJob($job, $revision);
        abort_unless($revision->issued_at !== null && $revision->issued_evidence !== null, 404);

        return view('job-revisions.issued', [
            'job' => $job,
            'revision' => $revision,
            'issuedQuote' => $revision->issued_evidence,
        ]);
    }

    public function markPending(Job $job, JobRevision $revision): RedirectResponse
    {
        $this->ensureRevisionBelongsToJob($job, $revision);
        $this->workspaceManager->markPending($revision);

        return redirect()
            ->route('jobs.revisions.show', ['job' => $job, 'revision' => $revision])
            ->with('status', 'Quote marked as pending.');
    }

    public function returnToDraft(Job $job, JobRevision $revision): RedirectResponse
    {
        $this->ensureRevisionBelongsToJob($job, $revision);
        $draftRevision = $this->workspaceManager->returnToDraft($revision);

        return redirect()
            ->route('jobs.revisions.show', ['job' => $job, 'revision' => $draftRevision])
            ->with('status', 'New draft revision created from the issued quote.');
    }

    public function book(Job $job, JobRevision $revision): RedirectResponse
    {
        $this->ensureRevisionBelongsToJob($job, $revision);
        $this->workspaceManager->book($revision);

        return redirect()
            ->route('jobs.revisions.show', ['job' => $job, 'revision' => $revision])
            ->with('status', 'Quote marked as booked.');
    }

    public function complete(Job $job, JobRevision $revision): RedirectResponse
    {
        $this->ensureRevisionBelongsToJob($job, $revision);
        $this->workspaceManager->complete($revision);

        return redirect()
            ->route('jobs.revisions.show', ['job' => $job, 'revision' => $revision])
            ->with('status', 'Job marked as completed.');
    }

    private function ensureRevisionBelongsToJob(Job $job, JobRevision $revision): void
    {
        abort_unless($revision->job_id === $job->id, 404);
    }
}
