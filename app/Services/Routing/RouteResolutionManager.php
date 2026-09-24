<?php

namespace App\Services\Routing;

use App\Models\RateSetting;
use App\Models\RouteResolution;
use App\Models\TransportEnquiry;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RouteResolutionManager
{
    public function __construct(private readonly RouteDistanceAdapter $adapter) {}

    public function resolve(TransportEnquiry $enquiry): RouteResolution
    {
        return $this->resolveAttempt($enquiry, null, 'initial');
    }

    public function retry(TransportEnquiry $enquiry, RouteResolution $previousResolution): RouteResolution
    {
        return $this->resolveAttempt($enquiry, $previousResolution, 'retry');
    }

    public function resolveCorrection(TransportEnquiry $enquiry, RouteResolution $previousResolution): RouteResolution
    {
        return $this->resolveAttempt($enquiry, $previousResolution, 'correction');
    }

    private function resolveAttempt(
        TransportEnquiry $enquiry,
        ?RouteResolution $previousResolution,
        string $attemptKind,
    ): RouteResolution {
        $depotPostcode = (string) RateSetting::query()->active()->latest('effective_from')->value('depot_postcode');
        $requestStartedAt = now();
        $requestContext = $this->requestContext($enquiry);
        $resolution = $this->createPendingResolution(
            $enquiry,
            $depotPostcode,
            $requestStartedAt,
            $requestContext,
            $previousResolution,
            $attemptKind,
        );
        $request = new RouteResolutionRequest($resolution->id, $depotPostcode, (string) $enquiry->pickup_postcode, (string) $enquiry->dropoff_postcode, $requestStartedAt, $requestContext);
        $result = $this->adapter->resolve($request);
        $responseReceivedAt = now();
        $latencyMilliseconds = $requestStartedAt->diffInMilliseconds($responseReceivedAt);

        return DB::transaction(fn (): RouteResolution => $this->persistRouteResult($resolution, $enquiry, $result, $requestStartedAt, $responseReceivedAt, $latencyMilliseconds));
    }

    private function createPendingResolution(
        TransportEnquiry $enquiry,
        string $depotPostcode,
        CarbonInterface $requestStartedAt,
        array $requestContext,
        ?RouteResolution $previousResolution,
        string $attemptKind,
    ): RouteResolution {
        $resolution = RouteResolution::query()->create([
            'transport_enquiry_id' => $enquiry->id,
            'previous_route_resolution_id' => $previousResolution?->id,
            'provider' => 'here',
            'provider_product' => 'geocoding_and_routing_v8',
            'route_profile' => RouteResolutionRequest::ROUTE_PROFILE,
            'attempt_kind' => $attemptKind,
            'overall_status' => 'pending',
            'operator_action_required' => false,
            'pricing_eligible' => false,
            'raw_input_snapshot' => $this->rawInputSnapshot([
                'depot_postcode' => $depotPostcode,
                'pickup_postcode' => $enquiry->pickup_postcode,
                'dropoff_postcode' => $enquiry->dropoff_postcode,
            ]),
            'request_context' => $requestContext,
            'normalisation_metadata' => [],
            'provider_metadata' => ['routing_version' => 'v8', 'transport_mode' => RouteResolutionRequest::ROUTE_PROFILE],
            'warnings' => [],
            'failure_detail' => null,
            'attempted_at' => $requestStartedAt,
            'request_started_at' => $requestStartedAt,
        ]);
        $resolution->update(['request_id' => (string) $resolution->id]);

        return $resolution;
    }

    private function requestContext(TransportEnquiry $enquiry): array
    {
        return ['transport_enquiry_id' => $enquiry->id];
    }

    private function persistRouteResult(RouteResolution $resolution, TransportEnquiry $enquiry, array $result, CarbonInterface $requestStartedAt, CarbonInterface $responseReceivedAt, int $latencyMilliseconds): RouteResolution
    {
        $resolution->fill($this->routeResolutionValues($enquiry, $result, $requestStartedAt, $responseReceivedAt, $latencyMilliseconds));
        $resolution->save();
        $this->replaceLegs($resolution, $result['legs']);

        return $resolution->load('legs');
    }

    private function routeResolutionValues(TransportEnquiry $enquiry, array $result, CarbonInterface $requestStartedAt, CarbonInterface $responseReceivedAt, int $latencyMilliseconds): array
    {
        return [
            'transport_enquiry_id' => $enquiry->id,
            'provider' => $result['provider'],
            'provider_product' => $result['provider_product'],
            'route_profile' => RouteResolutionRequest::ROUTE_PROFILE,
            'overall_status' => $result['overall_status'],
            'operator_action_required' => $result['operator_action_required'],
            'pricing_eligible' => $result['pricing_eligible'],
            'raw_input_snapshot' => $this->rawInputSnapshot($result['raw_input_snapshot']),
            'normalisation_metadata' => $this->normalisationMetadata($result['normalisation_metadata']),
            'provider_metadata' => $this->providerMetadata($result['provider_metadata']),
            'warnings' => $this->warnings($result['warnings']),
            'failure_detail' => $this->failureDetail($result['failure_detail']),
            'resolved_at' => $this->hasResolvedTimestamp($result['overall_status']) ? $responseReceivedAt : null,
            'attempted_at' => $requestStartedAt,
            'request_started_at' => $requestStartedAt,
            'response_received_at' => $responseReceivedAt,
            'latency_milliseconds' => $latencyMilliseconds,
        ];
    }

    private function hasResolvedTimestamp(string $status): bool
    {
        return in_array($status, ['resolved', 'resolved_with_warning', 'operator_review_required'], true);
    }

    private function replaceLegs(RouteResolution $resolution, array $legs): void
    {
        foreach ($legs as $leg) {
            $resolution->legs()->create($this->routeLegValues($leg));
        }
    }

    private function routeLegValues(array $leg): array
    {
        return [
            'sequence' => $leg['sequence'],
            'leg_type' => $leg['leg_type'],
            'origin_input' => $leg['origin_input'],
            'destination_input' => $leg['destination_input'],
            'resolved_origin_metadata' => $this->locationMetadata($leg['resolved_origin_metadata']),
            'resolved_destination_metadata' => $this->locationMetadata($leg['resolved_destination_metadata']),
            'status' => $leg['status'],
            'distance_metres' => $leg['distance_metres'],
            'quoted_miles' => $leg['quoted_miles'],
            'duration_seconds' => $leg['duration_seconds'],
            'provider_route_id' => $leg['provider_route_id'],
            'warnings' => $this->warnings($leg['warnings']),
            'failure_detail' => $this->failureDetail($leg['failure_detail']),
        ];
    }

    private function rawInputSnapshot(array $snapshot): array
    {
        return [
            'depot_postcode' => $snapshot['depot_postcode'] ?? null,
            'pickup_postcode' => $snapshot['pickup_postcode'] ?? null,
            'dropoff_postcode' => $snapshot['dropoff_postcode'] ?? null,
        ];
    }

    private function normalisationMetadata(array $metadata): array
    {
        $locations = [];

        foreach ($metadata['locations'] ?? [] as $location) {
            $normalisedLocation = $this->locationMetadata($location);
            if ($normalisedLocation !== null) {
                $locations[] = $normalisedLocation;
            }
        }

        return $locations === [] ? [] : ['locations' => $locations];
    }

    private function locationMetadata(?array $location): ?array
    {
        if ($location === null) {
            return null;
        }

        return array_filter([
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'postcode' => $location['postcode'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function providerMetadata(array $metadata): array
    {
        return array_filter([
            'routing_version' => $metadata['routing_version'] ?? null,
            'transport_mode' => RouteResolutionRequest::ROUTE_PROFILE,
            'http_status' => $metadata['http_status'] ?? null,
            'error_category' => $metadata['error_category'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function warnings(array $warnings): array
    {
        $structuredWarnings = [];

        foreach ($warnings as $warning) {
            if (is_array($warning) && is_string($warning['code'] ?? null) && is_string($warning['message'] ?? null)) {
                $structuredWarnings[] = $this->warning($warning);
            }
        }

        return $structuredWarnings;
    }

    private function warning(array $warning): array
    {
        return [
            'code' => $warning['code'],
            'message' => $warning['message'],
            'context' => $this->warningContext($warning['context'] ?? []),
        ];
    }

    private function warningContext(array $context): array
    {
        return array_filter([
            'field' => $context['field'] ?? null,
            'input' => $context['input'] ?? null,
            'canonical_postcode' => $context['canonical_postcode'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function failureDetail(?array $failure): ?array
    {
        if ($failure === null) {
            return null;
        }

        return [
            'code' => $failure['code'] ?? null,
            'message' => $failure['message'] ?? null,
            'context' => $this->failureContext($failure['context'] ?? []),
        ];
    }

    private function failureContext(array $context): array
    {
        return array_filter([
            'stage' => $context['stage'] ?? null,
            'field' => $context['field'] ?? null,
            'http_status' => $context['http_status'] ?? null,
            'error_category' => $context['error_category'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
