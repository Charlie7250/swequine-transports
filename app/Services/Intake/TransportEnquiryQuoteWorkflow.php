<?php

namespace App\Services\Intake;

use App\Models\JobRevision;
use App\Models\RouteResolution;
use App\Models\TransportEnquiry;
use App\Models\User;
use App\Services\Quotes\QuoteWorkspaceManager;
use App\Services\Routing\RouteResolutionManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TransportEnquiryQuoteWorkflow
{
    private const RESOLVE_SUBMISSION_SESSION_PREFIX = 'transport_enquiries.resolve_submissions.';

    public function __construct(
        private readonly RouteResolutionManager $routeResolutionManager,
        private readonly QuoteWorkspaceManager $workspaceManager,
    ) {}

    public function startResolveSubmission(): string
    {
        $submissionToken = (string) Str::uuid();

        session()->put($this->resolveSubmissionSessionKey($submissionToken), ['status' => 'available']);

        return $submissionToken;
    }

    public function resolve(array $attributes, string $submissionToken): RouteResolution
    {
        $submission = session()->get($this->resolveSubmissionSessionKey($submissionToken));

        if (! is_array($submission)) {
            throw $this->invalidResolveSubmission();
        }

        if (isset($submission['route_resolution_id'])) {
            return RouteResolution::query()->findOrFail($submission['route_resolution_id']);
        }

        if ($submission['status'] !== 'available') {
            throw $this->invalidResolveSubmission();
        }

        session()->put($this->resolveSubmissionSessionKey($submissionToken), ['status' => 'processing']);

        return $this->resolveNewEnquiry($attributes, $submissionToken);
    }

    public function accept(TransportEnquiry $enquiry, RouteResolution $resolution, ?int $actorId): JobRevision
    {
        return $this->workspaceManager->createFromAcceptedRoute($enquiry, $resolution, $actorId);
    }

    public function retry(TransportEnquiry $enquiry, RouteResolution $resolution, int $actorId): RouteResolution
    {
        $this->ensureExceptionActorAuthorized($actorId);
        $this->ensureRouteResolutionBelongsToEnquiry($enquiry, $resolution);

        return $this->routeResolutionManager->retry($enquiry, $resolution);
    }

    public function correct(RouteResolution $resolution, TransportEnquiry $enquiry, array $attributes, int $actorId): RouteResolution
    {
        $this->ensureExceptionActorAuthorized($actorId);
        $this->ensureRouteResolutionBelongsToEnquiry($enquiry, $resolution);

        $enquiry->update([
            'pickup_postcode' => $attributes['pickup_postcode'],
            'dropoff_postcode' => $attributes['dropoff_postcode'],
        ]);

        return $this->routeResolutionManager->resolveCorrection($enquiry->fresh(), $resolution);
    }

    private function resolveNewEnquiry(array $attributes, string $submissionToken): RouteResolution
    {
        try {
            $enquiry = TransportEnquiry::query()->create(array_merge($attributes, ['status' => 'draft']));
            $resolution = $this->routeResolutionManager->resolve($enquiry);
        } catch (Throwable $exception) {
            session()->put($this->resolveSubmissionSessionKey($submissionToken), ['status' => 'available']);

            throw $exception;
        }

        session()->put($this->resolveSubmissionSessionKey($submissionToken), [
            'route_resolution_id' => $resolution->id,
        ]);

        return $resolution;
    }

    private function invalidResolveSubmission(): ValidationException
    {
        return ValidationException::withMessages([
            'submission_token' => 'This route submission could not be verified. Return to the transport enquiry and try again.',
        ]);
    }

    private function ensureRouteResolutionBelongsToEnquiry(TransportEnquiry $enquiry, RouteResolution $resolution): void
    {
        if ($resolution->transport_enquiry_id === $enquiry->id) {
            return;
        }

        throw ValidationException::withMessages([
            'route' => 'This route does not belong to the selected transport enquiry.',
        ]);
    }

    private function ensureExceptionActorAuthorized(int $actorId): void
    {
        if (User::query()->whereKey($actorId)->where('can_manage_quote_exceptions', true)->exists()) {
            return;
        }

        throw new AuthorizationException('You are not authorised to manage quote exceptions.');
    }

    private function resolveSubmissionSessionKey(string $submissionToken): string
    {
        return self::RESOLVE_SUBMISSION_SESSION_PREFIX.$submissionToken;
    }
}
