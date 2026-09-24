<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveTransportEnquiryRequest;
use App\Models\RouteResolution;
use App\Models\TransportEnquiry;
use App\Services\Intake\TransportEnquiryQuoteWorkflow;
use App\Services\Intake\TransportEnquiryRouteReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransportEnquiryQuoteController extends Controller
{
    public function create(TransportEnquiryQuoteWorkflow $workflow): View
    {
        return view('transport-enquiries.create', [
            'submissionToken' => $workflow->startResolveSubmission(),
        ]);
    }

    public function resolve(
        ResolveTransportEnquiryRequest $request,
        TransportEnquiryQuoteWorkflow $workflow,
    ): RedirectResponse {
        $attributes = $request->validated();
        $submissionToken = $attributes['submission_token'];
        unset($attributes['submission_token']);

        $resolution = $workflow->resolve($attributes, $submissionToken);

        return redirect()->route('transport-enquiries.route-review', [
            'transportEnquiry' => $resolution->transport_enquiry_id,
            'routeResolution' => $resolution->id,
        ]);
    }

    public function review(
        TransportEnquiry $transportEnquiry,
        RouteResolution $routeResolution,
        Request $request,
        TransportEnquiryRouteReview $routeReview,
    ): View {
        abort_unless($routeResolution->transport_enquiry_id === $transportEnquiry->id, 404);

        $routeReviewData = $routeReview->data($transportEnquiry, $routeResolution);

        if (! $request->user()?->canManageQuoteExceptions()) {
            $routeReviewData['canHandleException'] = false;

            if ($routeResolution->overall_status === 'operator_review_required') {
                $routeReviewData['canAccept'] = false;
            }
        }

        return view('transport-enquiries.route-review', $routeReviewData);
    }

    public function accept(
        TransportEnquiry $transportEnquiry,
        RouteResolution $routeResolution,
        Request $request,
        TransportEnquiryQuoteWorkflow $workflow,
    ): RedirectResponse {
        $revision = $workflow->accept($transportEnquiry, $routeResolution, $request->user()?->id);

        return redirect()
            ->route('jobs.revisions.show', ['job' => $revision->job_id, 'revision' => $revision->id])
            ->with('status', 'Draft quote saved.');
    }
}
