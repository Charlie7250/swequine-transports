<?php

namespace App\Services\Quotes;

use App\Models\JobRevision;
use App\Models\RouteLeg;

class IssuedQuoteChecklist
{
    private const REVISION_ROUTE_LEG_LABELS = [
        'depot_to_pickup',
        'pickup_to_dropoff',
        'dropoff_to_depot',
    ];

    private const RESOLUTION_ROUTE_LEG_TYPES = [
        'depot_to_pickup',
        'pickup_to_drop_off',
        'drop_off_to_depot',
    ];

    public function for(JobRevision $revision): array
    {
        $revision->loadMissing([
            'job.customer',
            'quoteExceptionAudits',
            'rateSetting',
            'routeLegs',
            'routeResolution.legs',
            'routeResolution.reviewDecisions',
            'routeResolution.transportEnquiry',
            'weeklyFuelPrice',
        ]);

        if (! $this->hasAcceptedEligibleRoute($revision)) {
            return [$this->item('Route-backed accepted route', false)];
        }

        $enquiry = $revision->routeResolution->transportEnquiry;

        return [
            $this->item(
                'Customer contact',
                filled($revision->job->customer?->name)
                    && (filled($revision->job->customer?->email) || filled($revision->job->customer?->phone)),
            ),
            $this->item('Enquiry source', filled($enquiry?->source)),
            $this->item(
                'Requested date or date to be arranged',
                $enquiry?->requested_date !== null || $enquiry?->date_to_be_arranged === true,
            ),
            $this->item('Constraints acknowledged', $enquiry?->special_constraints_acknowledged === true),
            $this->item(
                'Active fuel and rate context',
                $this->hasEligiblePricingContext($revision),
            ),
            $this->item('Complete three-leg route', $this->hasCompleteRoute($revision)),
            $this->item('Route review decision and exception evidence', $this->hasRouteReviewEvidence($revision)),
            $this->item('Quote review confirmation', false),
        ];
    }

    public function isComplete(JobRevision $revision): bool
    {
        return collect($this->for($revision))
            ->reject(fn (array $item): bool => $item['label'] === 'Quote review confirmation')
            ->every(fn (array $item): bool => $item['complete']);
    }

    public function incompleteLabels(JobRevision $revision): array
    {
        return collect($this->for($revision))
            ->reject(fn (array $item): bool => $item['label'] === 'Quote review confirmation')
            ->filter(fn (array $item): bool => ! $item['complete'])
            ->pluck('label')
            ->all();
    }

    private function item(string $label, bool $complete): array
    {
        return [
            'label' => $label,
            'complete' => $complete,
        ];
    }

    private function hasCompleteRoute(JobRevision $revision): bool
    {
        $routeLegs = $revision->routeLegs->sortBy('sequence')->values();

        return $this->hasAcceptedEligibleRoute($revision)
            && $revision->routeResolution->legs->count() === 3
            && $routeLegs->count() === 3
            && $revision->routeResolution->legs->pluck('leg_type')->all() === self::RESOLUTION_ROUTE_LEG_TYPES
            && $routeLegs->pluck('label')->all() === self::REVISION_ROUTE_LEG_LABELS
            && $routeLegs->every(fn (RouteLeg $routeLeg): bool => $routeLeg->manual_miles !== null || $routeLeg->miles !== null);
    }

    private function hasEligiblePricingContext(JobRevision $revision): bool
    {
        $hasEligibleFuel = $revision->weeklyFuelPrice?->is_active === true
            || data_get($revision->calculation_explanation, 'fuel_context.selection_type') === 'explicit_correction'
                && data_get($revision->calculation_explanation, 'fuel_context.selected_weekly_fuel_price_id') === $revision->weekly_fuel_price_id;

        return $hasEligibleFuel && $revision->rateSetting?->is_active === true;
    }

    private function hasRouteReviewEvidence(JobRevision $revision): bool
    {
        $resolution = $revision->routeResolution;

        if ($resolution === null) {
            return true;
        }

        $requiresReview = $resolution->operator_action_required
            || $resolution->overall_status === 'operator_review_required';
        $hasReviewDecision = $resolution->reviewDecisions
            ->contains('decision', 'accepted_for_pricing')
            || $this->hasManualFallbackAudit($revision);
        $hasRouteChange = $revision->routeLegs->contains(
            fn (RouteLeg $routeLeg): bool => $routeLeg->manual_miles !== null,
        );
        $hasFinalTotalOverride = filled($revision->manual_final_total_reason);
        $hasExceptionAudit = $revision->quoteExceptionAudits->isNotEmpty();

        return (! $requiresReview || $hasReviewDecision)
            && (! $hasRouteChange && ! $hasFinalTotalOverride || $hasExceptionAudit);
    }

    private function hasAcceptedEligibleRoute(JobRevision $revision): bool
    {
        $resolution = $revision->routeResolution;
        $enquiry = $resolution?->transportEnquiry;

        if ($resolution === null || $enquiry?->quote_job_id !== $revision->job_id) {
            return false;
        }

        if ($this->hasManualFallbackAudit($revision)) {
            return in_array($resolution->overall_status, [
                'operator_review_required',
                'unresolved',
                'provider_unavailable',
                'provider_response_invalid',
            ], true);
        }

        if (in_array($resolution->overall_status, ['resolved', 'resolved_with_warning'], true)) {
            return $resolution->pricing_eligible;
        }

        return $resolution->overall_status === 'operator_review_required'
            && $resolution->reviewDecisions->contains('decision', 'accepted_for_pricing');
    }

    private function hasManualFallbackAudit(JobRevision $revision): bool
    {
        return $revision->quoteExceptionAudits
            ->contains('exception_type', 'manual_route_fallback');
    }
}
