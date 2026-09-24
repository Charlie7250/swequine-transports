<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectTransportEnquiryRouteRequest;
use App\Http\Requests\SaveFinalTotalOverrideRequest;
use App\Http\Requests\SaveManualRouteFallbackRequest;
use App\Http\Requests\SaveRouteLegOverridesRequest;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\RouteResolution;
use App\Models\TransportEnquiry;
use App\Services\Intake\TransportEnquiryQuoteWorkflow;
use App\Services\Intake\TransportEnquiryRouteReview;
use App\Services\Quotes\QuoteWorkspaceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuoteExceptionController extends Controller
{
    private const ROUTE_EXCEPTION_STATUSES = [
        'resolved_with_warning',
        'operator_review_required',
        'invalid_input',
        'unresolved',
        'provider_unavailable',
        'provider_response_invalid',
    ];

    public function routeException(
        TransportEnquiry $transportEnquiry,
        RouteResolution $routeResolution,
        TransportEnquiryRouteReview $routeReview,
    ): View {
        $this->ensureExceptionAuthority();
        $this->ensureRouteResolutionBelongsToEnquiry($transportEnquiry, $routeResolution);
        abort_unless(in_array($routeResolution->overall_status, self::ROUTE_EXCEPTION_STATUSES, true), 404);

        return view('transport-enquiries.route-exception', [
            'enquiry' => $transportEnquiry,
            'resolution' => $routeResolution->load('legs'),
            'canUseManualFallback' => $this->canUseManualFallback($routeResolution),
            'routeEvidence' => $routeReview->routeEvidence($routeResolution),
        ]);
    }

    public function retry(
        TransportEnquiry $transportEnquiry,
        RouteResolution $routeResolution,
        TransportEnquiryQuoteWorkflow $workflow,
    ): RedirectResponse {
        $this->ensureExceptionAuthority();
        $retryResolution = $workflow->retry($transportEnquiry, $routeResolution, (int) auth()->id());

        return redirect()->route('transport-enquiries.route-review', [
            'transportEnquiry' => $transportEnquiry,
            'routeResolution' => $retryResolution,
        ]);
    }

    public function correct(
        TransportEnquiry $transportEnquiry,
        RouteResolution $routeResolution,
        CorrectTransportEnquiryRouteRequest $request,
        TransportEnquiryQuoteWorkflow $workflow,
    ): RedirectResponse {
        $this->ensureExceptionAuthority();
        $correctedResolution = $workflow->correct($routeResolution, $transportEnquiry, $request->validated(), (int) $request->user()->id);

        return redirect()->route('transport-enquiries.route-review', [
            'transportEnquiry' => $transportEnquiry,
            'routeResolution' => $correctedResolution,
        ]);
    }

    public function manualFallback(
        TransportEnquiry $transportEnquiry,
        RouteResolution $routeResolution,
        SaveManualRouteFallbackRequest $request,
        QuoteWorkspaceManager $workspaceManager,
    ): RedirectResponse {
        $this->ensureExceptionAuthority();
        $this->ensureRouteResolutionBelongsToEnquiry($transportEnquiry, $routeResolution);
        $revision = $workspaceManager->createFromManualFallback(
            $transportEnquiry,
            $routeResolution,
            $request->validated(),
            (int) $request->user()->id,
        );

        return redirect()->route('jobs.revisions.show', ['job' => $revision->job_id, 'revision' => $revision->id]);
    }

    public function revisionException(Job $job, JobRevision $revision): View
    {
        $this->ensureExceptionAuthority();
        $this->ensureRevisionBelongsToJob($job, $revision);
        abort_unless($revision->routeResolution !== null, 404);

        return view('job-revisions.exceptions', [
            'job' => $job,
            'revision' => $revision->load(['routeLegs' => fn ($query) => $query->orderBy('sequence'), 'routeResolution.legs']),
            'canOverrideRouteLegs' => in_array($revision->routeResolution->overall_status, self::ROUTE_EXCEPTION_STATUSES, true),
        ]);
    }

    public function overrideRouteLegs(
        Job $job,
        JobRevision $revision,
        SaveRouteLegOverridesRequest $request,
        QuoteWorkspaceManager $workspaceManager,
    ): RedirectResponse {
        $this->ensureExceptionAuthority();
        $this->ensureRevisionBelongsToJob($job, $revision);
        $updatedRevision = $workspaceManager->overrideRouteLegs($revision, $request->validated(), (int) $request->user()->id);

        return redirect()->route('jobs.revisions.show', ['job' => $job, 'revision' => $updatedRevision]);
    }

    public function overrideFinalTotal(
        Job $job,
        JobRevision $revision,
        SaveFinalTotalOverrideRequest $request,
        QuoteWorkspaceManager $workspaceManager,
    ): RedirectResponse {
        $this->ensureExceptionAuthority();
        $this->ensureRevisionBelongsToJob($job, $revision);
        $updatedRevision = $workspaceManager->overrideFinalTotal($revision, $request->validated(), (int) $request->user()->id);

        return redirect()->route('jobs.revisions.show', ['job' => $job, 'revision' => $updatedRevision]);
    }

    private function canUseManualFallback(RouteResolution $resolution): bool
    {
        return in_array($resolution->overall_status, [
            'unresolved',
            'provider_unavailable',
            'provider_response_invalid',
            'operator_review_required',
        ], true);
    }

    private function ensureRouteResolutionBelongsToEnquiry(TransportEnquiry $enquiry, RouteResolution $resolution): void
    {
        abort_unless($resolution->transport_enquiry_id === $enquiry->id, 404);
    }

    private function ensureRevisionBelongsToJob(Job $job, JobRevision $revision): void
    {
        abort_unless($revision->job_id === $job->id, 404);
    }

    private function ensureExceptionAuthority(): void
    {
        abort_unless(auth()->user()?->canManageQuoteExceptions(), 403);
    }
}
