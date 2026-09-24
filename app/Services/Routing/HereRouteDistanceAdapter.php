<?php

namespace App\Services\Routing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HereRouteDistanceAdapter implements RouteDistanceAdapter
{
    public function resolve(RouteResolutionRequest $request): array
    {
        $depotPostcode = $request->depotPostcode;
        $pickupPostcode = $request->pickupPostcode;
        $dropoffPostcode = $request->dropoffPostcode;
        $postcodes = [$depotPostcode, $pickupPostcode, $dropoffPostcode];
        $invalidField = $this->invalidPostcodeField($postcodes);
        if ($invalidField !== null) {
            return $this->failureResult($request, $postcodes, 'invalid_input', 'A valid UK postcode is required for routing.', 'validation', ['field' => $invalidField]);
        }
        if (blank(config('services.here.api_key'))) {
            return $this->failureResult($request, $postcodes, 'provider_unavailable', 'HERE API key is not configured.', 'configuration');
        }
        $geocodes = $this->geocodePostcodes($postcodes);
        $geocodingFailure = $this->geocodingFailure($geocodes);
        if ($geocodingFailure !== null) {
            return $this->failureResult($request, $postcodes, ...$geocodingFailure);
        }

        $locations = $geocodes['locations'];
        $route = $this->route($locations);
        $routeFailure = $this->routeFailure($route);
        if ($routeFailure !== null) {
            return $this->failureResult($request, $postcodes, ...$routeFailure);
        }

        return $this->resolvedResult($request, $locations, $route);
    }

    private function geocodingFailure(array $geocodes): ?array
    {
        if ($geocodes['has_invalid_result']) {
            return ['provider_response_invalid', 'HERE geocoding returned an untrusted location.', 'geocoding', [], $geocodes['diagnostics']];
        }
        if ($geocodes['has_empty_result']) {
            return ['invalid_input', 'HERE could not find a UK postcode location.'];
        }
        if ($geocodes['is_provider_unavailable']) {
            return ['provider_unavailable', 'HERE geocoding was unavailable.', 'geocoding', [], $geocodes['diagnostics']];
        }

        return null;
    }

    private function invalidPostcodeField(array $postcodes): ?string
    {
        $fields = ['depot_postcode' => $postcodes[0], 'pickup_postcode' => $postcodes[1], 'dropoff_postcode' => $postcodes[2]];

        foreach ($fields as $field => $postcode) {
            if (! $this->isValidUkPostcode($postcode)) {
                return $field;
            }
        }

        return null;
    }

    private function isValidUkPostcode(string $postcode): bool
    {
        return preg_match('/^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/i', trim($postcode)) === 1;
    }

    private function routeFailure(array $route): ?array
    {
        if ($route['is_provider_unavailable']) {
            return ['provider_unavailable', 'HERE routing was unavailable.', 'routing', [], $route['diagnostics']];
        }
        if ($route['is_provider_response_invalid']) {
            return ['provider_response_invalid', 'HERE routing returned an invalid response.', 'routing', [], $route['diagnostics']];
        }
        if (count($route['sections']) < 3) {
            return ['unresolved', 'HERE returned fewer than three route sections.', 'routing'];
        }
        if (count($route['sections']) > 3) {
            return ['provider_response_invalid', 'HERE returned an unexpected number of route sections.', 'routing'];
        }
        if (! $this->hasValidRouteSections($route['sections'])) {
            return ['provider_response_invalid', 'HERE routing returned an incomplete route section.', 'routing'];
        }

        return null;
    }

    private function resolvedResult(RouteResolutionRequest $request, array $locations, array $route): array
    {
        $postcodes = [$request->depotPostcode, $request->pickupPostcode, $request->dropoffPostcode];
        $requiresReview = $this->requiresReview($locations, $postcodes);
        $warnings = $this->postcodeNormalisationWarnings($locations, $postcodes);
        $status = $requiresReview ? 'operator_review_required' : ($warnings === [] ? 'resolved' : 'resolved_with_warning');

        return [
            'provider' => 'here',
            'provider_product' => 'geocoding_and_routing_v8',
            'request_id' => (string) $request->resolutionId,
            'route_profile' => RouteResolutionRequest::ROUTE_PROFILE,
            'overall_status' => $status,
            'operator_action_required' => $requiresReview,
            'pricing_eligible' => ! $requiresReview,
            'raw_input_snapshot' => ['depot_postcode' => $postcodes[0], 'pickup_postcode' => $postcodes[1], 'dropoff_postcode' => $postcodes[2]],
            'normalisation_metadata' => ['locations' => $locations],
            'provider_metadata' => ['routing_version' => 'v8', 'transport_mode' => 'car'],
            'warnings' => $warnings,
            'legs' => $this->legs($postcodes, $locations, $route),
            'failure_detail' => null,
        ];
    }

    private function geocodePostcodes(array $postcodes): array
    {
        $locations = [];

        foreach ($postcodes as $postcode) {
            $location = $this->geocode($postcode);
            if ($location['is_provider_unavailable']) {
                return ['locations' => [], 'has_empty_result' => false, 'has_invalid_result' => false, 'is_provider_unavailable' => true, 'diagnostics' => $location['diagnostics']];
            }
            if ($location['is_provider_response_invalid']) {
                return ['locations' => [], 'has_empty_result' => false, 'has_invalid_result' => true, 'is_provider_unavailable' => false, 'diagnostics' => $location['diagnostics']];
            }
            if ($location['postcode'] === '') {
                return ['locations' => [], 'has_empty_result' => true, 'has_invalid_result' => false, 'is_provider_unavailable' => false, 'diagnostics' => []];
            }
            $locations[] = $location;
        }

        return ['locations' => $locations, 'has_empty_result' => false, 'has_invalid_result' => false, 'is_provider_unavailable' => false, 'diagnostics' => []];
    }

    private function geocode(string $postcode): array
    {
        try {
            $response = Http::acceptJson()->get(config('services.here.geocoding_url'), $this->geocodeQuery($postcode));
        } catch (ConnectionException) {
            return $this->unavailableLocation(['error_category' => 'connection']);
        }
        if ($this->isUnavailable($response)) {
            return $this->unavailableLocation($this->responseDiagnostics($response));
        }
        if ($this->isInvalidResponse($response)) {
            return $this->invalidLocation($this->responseDiagnostics($response));
        }
        $items = $response->json('items');
        if ($items === []) {
            return $this->emptyLocation();
        }
        if (! is_array($items)) {
            return $this->invalidLocation();
        }
        $item = $items[0] ?? null;
        if (! $this->isTrustedGeocodeItem($item)) {
            return $this->invalidLocation();
        }

        return [
            'latitude' => (float) data_get($item, 'position.lat', 0),
            'longitude' => (float) data_get($item, 'position.lng', 0),
            'postcode' => (string) data_get($item, 'address.postalCode', ''),
            'result_type' => (string) data_get($item, 'resultType', ''),
            'is_provider_unavailable' => false,
            'is_provider_response_invalid' => false,
            'diagnostics' => [],
        ];
    }

    private function isTrustedGeocodeItem(mixed $item): bool
    {
        if (! is_array($item) || strtoupper((string) data_get($item, 'address.countryCode')) !== 'GB') {
            return false;
        }
        if (! is_string(data_get($item, 'address.postalCode')) || blank(data_get($item, 'address.postalCode'))) {
            return false;
        }

        return $this->isValidCoordinate(data_get($item, 'position.lat'), -90, 90)
            && $this->isValidCoordinate(data_get($item, 'position.lng'), -180, 180);
    }

    private function isValidCoordinate(mixed $value, float $minimum, float $maximum): bool
    {
        return is_numeric($value) && is_finite((float) $value) && $value >= $minimum && $value <= $maximum;
    }

    private function geocodeQuery(string $postcode): array
    {
        return ['q' => $postcode, 'in' => 'countryCode:GB', 'apiKey' => config('services.here.api_key')];
    }

    private function unavailableLocation(array $diagnostics = []): array
    {
        return ['latitude' => 0, 'longitude' => 0, 'postcode' => '', 'result_type' => '', 'is_provider_unavailable' => true, 'is_provider_response_invalid' => false, 'diagnostics' => $diagnostics];
    }

    private function invalidLocation(array $diagnostics = []): array
    {
        return ['latitude' => 0, 'longitude' => 0, 'postcode' => '', 'result_type' => '', 'is_provider_unavailable' => false, 'is_provider_response_invalid' => true, 'diagnostics' => $diagnostics];
    }

    private function emptyLocation(): array
    {
        return ['latitude' => 0, 'longitude' => 0, 'postcode' => '', 'result_type' => '', 'is_provider_unavailable' => false, 'is_provider_response_invalid' => false, 'diagnostics' => []];
    }

    private function route(array $locations): array
    {
        try {
            $response = Http::acceptJson()->get($this->routingUrl($locations));
        } catch (ConnectionException) {
            return $this->unavailableRoute(['error_category' => 'connection']);
        }
        if ($this->isUnavailable($response)) {
            return $this->unavailableRoute($this->responseDiagnostics($response));
        }
        if ($this->isInvalidResponse($response)) {
            return $this->invalidRoute($this->responseDiagnostics($response));
        }
        $routes = $response->json('routes');
        if ($routes === []) {
            return [
                'id' => null,
                'sections' => [],
                'is_provider_unavailable' => false,
                'is_provider_response_invalid' => false,
                'diagnostics' => [],
            ];
        }
        $route = is_array($routes) ? ($routes[0] ?? null) : null;
        if (! is_array($route) || ! is_array($route['sections'] ?? null)) {
            return $this->invalidRoute();
        }

        return [
            'id' => $route['id'] ?? null,
            'sections' => $route['sections'],
            'is_provider_unavailable' => false,
            'is_provider_response_invalid' => false,
            'diagnostics' => [],
        ];
    }

    private function unavailableRoute(array $diagnostics = []): array
    {
        return ['id' => null, 'sections' => [], 'is_provider_unavailable' => true, 'is_provider_response_invalid' => false, 'diagnostics' => $diagnostics];
    }

    private function invalidRoute(array $diagnostics = []): array
    {
        return ['id' => null, 'sections' => [], 'is_provider_unavailable' => false, 'is_provider_response_invalid' => true, 'diagnostics' => $diagnostics];
    }

    private function isUnavailable(Response $response): bool
    {
        return in_array($response->status(), [401, 403, 408, 429], true) || $response->serverError();
    }

    private function isInvalidResponse(Response $response): bool
    {
        return $response->clientError();
    }

    private function responseDiagnostics(Response $response): array
    {
        return ['http_status' => $response->status(), 'error_category' => $this->responseErrorCategory($response)];
    }

    private function responseErrorCategory(Response $response): string
    {
        return match ($response->status()) {
            401, 403 => 'authentication',
            408 => 'timeout',
            429 => 'quota',
            default => $response->serverError() ? 'service' : 'client_error',
        };
    }

    private function hasValidRouteSections(array $sections): bool
    {
        foreach (array_slice($sections, 0, 3) as $section) {
            if (! is_numeric(data_get($section, 'summary.length')) || ! is_numeric(data_get($section, 'summary.duration'))) {
                return false;
            }
        }

        return true;
    }

    private function routingUrl(array $locations): string
    {
        $query = http_build_query([
            'apiKey' => config('services.here.api_key'),
            'transportMode' => 'car',
            'origin' => $this->coordinate($locations[0]),
            'destination' => $this->coordinate($locations[0]),
            'return' => 'summary',
        ]);

        return config('services.here.routing_url').'?'.$query.'&via='.rawurlencode($this->via($locations[1])).'&via='.rawurlencode($this->via($locations[2]));
    }

    private function coordinate(array $location): string
    {
        return $location['latitude'].','.$location['longitude'];
    }

    private function via(array $location): string
    {
        return $this->coordinate($location).'!passThrough=false';
    }

    private function requiresReview(array $locations, array $postcodes): bool
    {
        foreach ($locations as $index => $location) {
            if ($location['result_type'] !== 'postalCode' || $this->normalisePostcode($location['postcode']) !== $this->normalisePostcode($postcodes[$index])) {
                return true;
            }
        }

        return false;
    }

    private function postcodeNormalisationWarnings(array $locations, array $postcodes): array
    {
        $fields = ['depot_postcode', 'pickup_postcode', 'dropoff_postcode'];
        $warnings = [];

        foreach ($locations as $index => $location) {
            if ($location['postcode'] !== $postcodes[$index] && $this->normalisePostcode($location['postcode']) === $this->normalisePostcode($postcodes[$index])) {
                $warnings[] = [
                    'code' => 'postcode_normalised',
                    'message' => 'HERE normalised the postcode.',
                    'context' => ['field' => $fields[$index], 'input' => $postcodes[$index], 'canonical_postcode' => $location['postcode']],
                ];
            }
        }

        return $warnings;
    }

    private function normalisePostcode(string $postcode): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($postcode)) ?? '');
    }

    private function legs(array $postcodes, array $locations, array $route): array
    {
        $legTypes = ['depot_to_pickup', 'pickup_to_drop_off', 'drop_off_to_depot'];

        return array_map(function (string $legType, int $index) use ($postcodes, $locations, $route): array {
            $summary = data_get($route, "sections.{$index}.summary", []);
            $distance = (int) data_get($summary, 'length', 0);

            return [
                'sequence' => $index + 1,
                'leg_type' => $legType,
                'origin_input' => $postcodes[$index],
                'destination_input' => $postcodes[$index + 1] ?? $postcodes[0],
                'resolved_origin_metadata' => $locations[$index],
                'resolved_destination_metadata' => $locations[$index + 1] ?? $locations[0],
                'status' => 'resolved',
                'distance_metres' => $distance,
                'quoted_miles' => (int) round($distance / 1609.344),
                'duration_seconds' => (int) data_get($summary, 'duration', 0),
                'provider_route_id' => $route['id'],
                'warnings' => [],
                'failure_detail' => null,
            ];
        }, $legTypes, array_keys($legTypes));
    }

    private function failureResult(RouteResolutionRequest $request, array $postcodes, string $status, string $message, string $stage = 'geocoding', array $context = [], array $diagnostics = []): array
    {
        $failure = ['code' => $status, 'message' => $message, 'context' => ['stage' => $stage, ...$diagnostics, ...$context]];

        return [
            'provider' => 'here',
            'provider_product' => 'geocoding_and_routing_v8',
            'request_id' => (string) $request->resolutionId,
            'route_profile' => RouteResolutionRequest::ROUTE_PROFILE,
            'overall_status' => $status,
            'operator_action_required' => false,
            'pricing_eligible' => false,
            'raw_input_snapshot' => ['depot_postcode' => $postcodes[0], 'pickup_postcode' => $postcodes[1], 'dropoff_postcode' => $postcodes[2]],
            'normalisation_metadata' => [],
            'provider_metadata' => ['routing_version' => 'v8', 'transport_mode' => 'car', ...$diagnostics],
            'warnings' => [],
            'legs' => $this->emptyLegs($postcodes, $status, $failure),
            'failure_detail' => $failure,
        ];
    }

    private function emptyLegs(array $postcodes, string $status, array $failure): array
    {
        $legTypes = ['depot_to_pickup', 'pickup_to_drop_off', 'drop_off_to_depot'];

        return array_map(function (string $legType, int $index) use ($postcodes, $status, $failure): array {
            return [
                'sequence' => $index + 1,
                'leg_type' => $legType,
                'origin_input' => $postcodes[$index],
                'destination_input' => $postcodes[$index + 1] ?? $postcodes[0],
                'resolved_origin_metadata' => null,
                'resolved_destination_metadata' => null,
                'status' => $status,
                'distance_metres' => null,
                'quoted_miles' => null,
                'duration_seconds' => null,
                'provider_route_id' => null,
                'warnings' => [],
                'failure_detail' => $failure,
            ];
        }, $legTypes, array_keys($legTypes));
    }
}
