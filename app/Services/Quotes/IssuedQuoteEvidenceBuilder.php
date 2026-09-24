<?php

namespace App\Services\Quotes;

use App\Models\JobRevision;
use App\Models\QuoteExceptionAudit;
use App\Models\RouteLeg;
use App\Models\RouteResolution;
use App\Models\RouteResolutionLeg;
use App\Models\RouteResolutionReviewDecision;
use Illuminate\Support\Carbon;

class IssuedQuoteEvidenceBuilder
{
    public function build(JobRevision $revision, Carbon $issuedAt): array
    {
        $revision->loadMissing([
            'job.customer',
            'quoteExceptionAudits.actor',
            'rateSetting',
            'routeLegs',
            'routeResolution.legs',
            'routeResolution.reviewDecisions.actor',
            'routeResolution.transportEnquiry',
            'weeklyFuelPrice',
        ]);

        return [
            'customer' => $this->customer($revision),
            'journey' => $this->journey($revision),
            'route' => $this->route($revision),
            'pricing' => $this->pricing($revision),
            'exceptions' => $this->exceptions($revision),
            'issued_at' => $issuedAt->toIso8601String(),
        ];
    }

    private function customer(JobRevision $revision): array
    {
        return [
            'name' => $revision->job->customer?->name,
            'email' => $revision->job->customer?->email,
            'phone' => $revision->job->customer?->phone,
        ];
    }

    private function journey(JobRevision $revision): array
    {
        $enquiry = $revision->routeResolution?->transportEnquiry;

        return [
            'pickup_postcode' => $revision->pickup_postcode,
            'dropoff_postcode' => $revision->dropoff_postcode,
            'horse_count' => $revision->horse_count,
            'requested_date' => $enquiry?->requested_date?->toDateString(),
            'date_to_be_arranged' => $enquiry?->date_to_be_arranged ?? false,
            'constraints' => $enquiry?->special_constraints,
            'source' => $enquiry?->source,
        ];
    }

    private function route(JobRevision $revision): array
    {
        $resolution = $revision->routeResolution;
        $resolutionLegs = $resolution?->legs->keyBy('sequence') ?? collect();

        return [
            'provider' => $resolution?->provider,
            'provider_product' => $resolution?->provider_product,
            'request_id' => $resolution?->request_id,
            'route_profile' => $resolution?->route_profile,
            'status' => $resolution?->overall_status,
            'provider_metadata' => $this->providerMetadata($resolution),
            'normalisation' => $this->normalisation($resolution),
            'warnings' => $this->warnings($resolution),
            'review_decisions' => $this->reviewDecisions($resolution),
            'legs' => $revision->routeLegs
                ->sortBy('sequence')
                ->map(fn (RouteLeg $routeLeg): array => $this->routeLeg(
                    $routeLeg,
                    $resolutionLegs->get($routeLeg->sequence),
                ))
                ->values()
                ->all(),
        ];
    }

    private function providerMetadata(?RouteResolution $resolution): array
    {
        return array_filter([
            'routing_version' => $resolution?->provider_metadata['routing_version'] ?? null,
            'transport_mode' => $resolution?->provider_metadata['transport_mode'] ?? null,
            'http_status' => $resolution?->provider_metadata['http_status'] ?? null,
            'error_category' => $resolution?->provider_metadata['error_category'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function normalisation(?RouteResolution $resolution): array
    {
        $locations = $resolution?->normalisation_metadata['locations'] ?? [];

        return ['locations' => collect($locations)
            ->filter(fn (mixed $location): bool => is_array($location))
            ->map(fn (array $location): array => $this->location($location))
            ->values()
            ->all()];
    }

    private function warnings(?RouteResolution $resolution): array
    {
        return collect($resolution?->warnings ?? [])
            ->filter(fn (mixed $warning): bool => is_array($warning))
            ->map(fn (array $warning): array => [
                'code' => $warning['code'] ?? null,
                'message' => $warning['message'] ?? null,
                'context' => array_filter([
                    'field' => $warning['context']['field'] ?? null,
                    'input' => $warning['context']['input'] ?? null,
                    'canonical_postcode' => $warning['context']['canonical_postcode'] ?? null,
                ], fn (mixed $value): bool => $value !== null),
            ])
            ->values()
            ->all();
    }

    private function reviewDecisions(?RouteResolution $resolution): array
    {
        return collect($resolution?->reviewDecisions ?? [])
            ->sortBy('recorded_at')
            ->map(fn (RouteResolutionReviewDecision $decision): array => [
                'decision' => $decision->decision,
                'operator' => $decision->actor?->name,
                'recorded_at' => $decision->recorded_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function routeLeg(RouteLeg $routeLeg, ?RouteResolutionLeg $resolutionLeg): array
    {
        return [
            'label' => $routeLeg->label,
            'start_postcode' => $routeLeg->start_postcode,
            'end_postcode' => $routeLeg->end_postcode,
            'miles' => $routeLeg->manual_miles ?? $routeLeg->miles,
            'rate_type' => $routeLeg->rate_type,
            'rate_per_mile' => $routeLeg->rate_per_mile,
            'amount' => $routeLeg->amount,
            'origin_input' => $resolutionLeg?->origin_input,
            'destination_input' => $resolutionLeg?->destination_input,
            'resolved_origin' => $this->location($resolutionLeg?->resolved_origin_metadata),
            'resolved_destination' => $this->location($resolutionLeg?->resolved_destination_metadata),
            'resolution_status' => $resolutionLeg?->status,
            'distance_metres' => $resolutionLeg?->distance_metres,
            'duration_seconds' => $resolutionLeg?->duration_seconds,
            'provider_route_id' => $resolutionLeg?->provider_route_id,
        ];
    }

    private function location(?array $location): array
    {
        return array_filter([
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'postcode' => $location['postcode'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function pricing(JobRevision $revision): array
    {
        return [
            'engine_total' => $revision->engine_total,
            'final_total' => $revision->final_total ?? $revision->engine_total,
            'calculation_explanation' => $revision->calculation_explanation,
            'fuel' => [
                'week_commencing' => $revision->weeklyFuelPrice?->week_commencing?->toDateString(),
                'source' => $revision->weeklyFuelPrice?->source,
                'price_per_litre_inc_vat' => $revision->weeklyFuelPrice?->price_per_litre_inc_vat,
            ],
            'rate_setting' => [
                'name' => $revision->rateSetting?->name,
                'depot_postcode' => $revision->rateSetting?->depot_postcode,
            ],
        ];
    }

    private function exceptions(JobRevision $revision): array
    {
        return $revision->quoteExceptionAudits
            ->sortBy('recorded_at')
            ->map(fn (QuoteExceptionAudit $audit): array => [
                'type' => $audit->exception_type,
                'reason_category' => $audit->reason_category,
                'explanation' => $audit->explanation,
                'original_value' => $audit->original_value,
                'replacement_value' => $audit->replacement_value,
                'operator' => $audit->actor?->name,
                'recorded_at' => $audit->recorded_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
