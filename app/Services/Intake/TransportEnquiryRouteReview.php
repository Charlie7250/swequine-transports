<?php

namespace App\Services\Intake;

use App\Models\RouteResolution;
use App\Models\TransportEnquiry;
use App\Services\Pricing\JobRevisionPricingContextResolver;

class TransportEnquiryRouteReview
{
    public function __construct(private readonly JobRevisionPricingContextResolver $pricingContextResolver) {}

    public function data(TransportEnquiry $enquiry, RouteResolution $resolution): array
    {
        $pricingContext = $this->pricingContextResolver->resolveForDraftWorkspace();
        $requiresManualPricingReview = $enquiry->horse_count > 2;

        return [
            'enquiry' => $enquiry,
            'resolution' => $resolution->load('legs'),
            'pricingContext' => $pricingContext,
            'canAccept' => ! $requiresManualPricingReview && $this->canAccept($resolution, $pricingContext),
            'canHandleException' => $this->canHandleException($resolution),
            'requiresManualPricingReview' => $requiresManualPricingReview,
            'routeEvidence' => $this->routeEvidence($resolution),
            'statusMessage' => $this->statusMessage($resolution),
        ];
    }

    public function routeEvidence(RouteResolution $resolution): array
    {
        $resolution->loadMissing('legs');

        return $resolution->legs->map(fn ($leg): array => [
            'leg_type' => $leg->leg_type,
            'entered_origin' => $leg->origin_input,
            'resolved_origin' => data_get($leg->resolved_origin_metadata, 'postcode') ?? 'Not resolved',
            'entered_destination' => $leg->destination_input,
            'resolved_destination' => data_get($leg->resolved_destination_metadata, 'postcode') ?? 'Not resolved',
            'failure_message' => $this->failureMessage($leg->failure_detail ?? $resolution->failure_detail),
        ])->all();
    }

    private function canAccept(RouteResolution $resolution, array $pricingContext): bool
    {
        return ($resolution->pricing_eligible
            && in_array($resolution->overall_status, ['resolved', 'resolved_with_warning'], true)
            || $resolution->overall_status === 'operator_review_required'
                && $resolution->legs()->whereNotNull('quoted_miles')->count() === 3)
            && $pricingContext['weekly_fuel_price'] !== null
            && $pricingContext['rate_setting'] !== null;
    }

    private function canHandleException(RouteResolution $resolution): bool
    {
        return in_array($resolution->overall_status, [
            'resolved_with_warning',
            'operator_review_required',
            'invalid_input',
            'unresolved',
            'provider_unavailable',
            'provider_response_invalid',
        ], true);
    }

    private function statusMessage(RouteResolution $resolution): string
    {
        return match ($resolution->overall_status) {
            'resolved' => 'Route calculated from the entered postcodes.',
            'resolved_with_warning' => 'Route calculated, but review the highlighted detail before continuing.',
            'operator_review_required' => 'This route needs your review before it can be used for pricing.',
            'invalid_input' => 'The route could not be calculated because one or more postcodes need attention.',
            'unresolved' => 'The route could not be calculated for one or more legs. Check the postcode before trying again.',
            'provider_unavailable' => 'Route lookup is temporarily unavailable. Your enquiry has been saved.',
            'provider_response_invalid' => 'The route result cannot be used safely. Retry the lookup or use approved exception handling.',
            default => 'The route is still being calculated.',
        };
    }

    private function failureMessage(?array $failureDetail): ?string
    {
        if ($failureDetail === null) {
            return null;
        }

        return match ($failureDetail['code'] ?? null) {
            'invalid_input' => 'Check the entered postcode for this route leg.',
            'unresolved' => 'No reliable route was found for this route leg.',
            'provider_unavailable' => 'Route lookup is temporarily unavailable for this route leg.',
            'provider_response_invalid' => 'The route result for this leg cannot be used safely.',
            default => 'This route leg needs attention before pricing.',
        };
    }
}
