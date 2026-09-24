<?php

namespace Tests\Feature\Routing;

use App\Services\Routing\HereRouteDistanceAdapter;
use App\Services\Routing\RouteResolutionRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HereRouteDistanceAdapterTest extends TestCase
{
    public function test_the_here_route_adapter_is_not_available_before_its_contract_is_created(): void
    {
        $this->assertTrue(class_exists('App\\Services\\Routing\\HereRouteDistanceAdapter'));
    }

    public function test_a_complete_here_route_resolves_three_eligible_ordered_rounded_mile_legs(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
        $geocodeIndex = 0;

        Http::fake(function (Request $request) use (&$geocodeIndex) {
            if (str_contains($request->url(), '/v1/geocode')) {
                $postcodes = ['EX16 0AA', 'EX1 1AA', 'EX2 2BB'];
                $postcode = $postcodes[$geocodeIndex++];

                return Http::response(['items' => [[
                    'resultType' => 'postalCode',
                    'address' => ['postalCode' => $postcode, 'countryCode' => 'GB'],
                    'position' => ['lat' => 50.7 + $geocodeIndex, 'lng' => -3.5 - $geocodeIndex],
                ]]]);
            }

            return Http::response(['routes' => [['id' => 'route-123', 'sections' => [
                ['summary' => ['length' => 1609, 'duration' => 600]],
                ['summary' => ['length' => 3218, 'duration' => 1200]],
                ['summary' => ['length' => 4828, 'duration' => 1800]],
            ]]]]);
        });

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('resolved', $result['overall_status']);
        $this->assertTrue($result['pricing_eligible']);
        $this->assertSame(['depot_to_pickup', 'pickup_to_drop_off', 'drop_off_to_depot'], array_column($result['legs'], 'leg_type'));
        $this->assertSame([1, 2, 3], array_column($result['legs'], 'quoted_miles'));
        Http::assertSentCount(4);
        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/v8/routes')
                && str_contains($request->url(), 'transportMode=car')
                && substr_count($request->url(), 'via=') === 2
                && str_contains($request->url(), 'passThrough%3Dfalse');
        });
    }

    public function test_a_partial_geocode_requires_operator_review_and_is_not_pricing_eligible(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
        $geocodeIndex = 0;

        Http::fake(function (Request $request) use (&$geocodeIndex) {
            if (str_contains($request->url(), '/v1/geocode')) {
                $items = [
                    ['resultType' => 'locality', 'address' => ['postalCode' => 'EX16', 'countryCode' => 'GB'], 'position' => ['lat' => 50.7, 'lng' => -3.5]],
                    ['resultType' => 'postalCode', 'address' => ['postalCode' => 'EX1 1AA', 'countryCode' => 'GB'], 'position' => ['lat' => 50.8, 'lng' => -3.4]],
                    ['resultType' => 'postalCode', 'address' => ['postalCode' => 'EX2 2BB', 'countryCode' => 'GB'], 'position' => ['lat' => 50.9, 'lng' => -3.3]],
                ];

                return Http::response(['items' => [$items[$geocodeIndex++]]]);
            }

            return Http::response(['routes' => [['sections' => [
                ['summary' => ['length' => 1609, 'duration' => 600]],
                ['summary' => ['length' => 3218, 'duration' => 1200]],
                ['summary' => ['length' => 4828, 'duration' => 1800]],
            ]]]]);
        });

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('operator_review_required', $result['overall_status']);
        $this->assertTrue($result['operator_action_required']);
        $this->assertFalse($result['pricing_eligible']);
        Http::assertSentCount(4);
    }

    public function test_an_empty_geocode_is_invalid_input_without_a_routing_request(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');

        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/v1/geocode')) {
                return Http::response(['items' => []]);
            }

            return Http::response(['routes' => [['sections' => []]]]);
        });

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('invalid_input', $result['overall_status']);
        $this->assertFalse($result['pricing_eligible']);
        Http::assertSentCount(1);
    }

    public function test_fewer_than_three_route_sections_are_unresolved_and_not_pricing_eligible(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
        $geocodeIndex = 0;

        Http::fake(function (Request $request) use (&$geocodeIndex) {
            if (str_contains($request->url(), '/v1/geocode')) {
                $postcodes = ['EX16 0AA', 'EX1 1AA', 'EX2 2BB'];
                $postcode = $postcodes[$geocodeIndex++];

                return Http::response(['items' => [[
                    'resultType' => 'postalCode',
                    'address' => ['postalCode' => $postcode, 'countryCode' => 'GB'],
                    'position' => ['lat' => 50.7, 'lng' => -3.5],
                ]]]);
            }

            return Http::response(['routes' => [['sections' => [
                ['summary' => ['length' => 1609, 'duration' => 600]],
                ['summary' => ['length' => 3218, 'duration' => 1200]],
            ]]]]);
        });

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('unresolved', $result['overall_status']);
        $this->assertFalse($result['pricing_eligible']);
        $this->assertCount(3, $result['legs']);
        Http::assertSentCount(4);
    }

    public function test_an_empty_routes_array_is_unresolved_with_three_failed_leg_slots(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
        $geocodeIndex = 0;

        Http::fake(function (Request $request) use (&$geocodeIndex) {
            if (str_contains($request->url(), '/v1/geocode')) {
                $postcodes = ['EX16 0AA', 'EX1 1AA', 'EX2 2BB'];

                return Http::response(['items' => [$this->validGeocodeItem($postcodes[$geocodeIndex++])]]);
            }

            return Http::response(['routes' => []]);
        });

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('unresolved', $result['overall_status']);
        $this->assertFalse($result['pricing_eligible']);
        $this->assertCount(3, $result['legs']);
        $this->assertSame(['unresolved', 'unresolved', 'unresolved'], array_column($result['legs'], 'status'));
        $this->assertNotSame('provider_response_invalid', $result['overall_status']);
    }

    public function test_a_missing_here_api_key_is_a_provider_unavailable_failure_without_requests(): void
    {
        config()->set('services.here.api_key', null);
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
        $geocodeIndex = 0;

        Http::fake(function (Request $request) use (&$geocodeIndex) {
            if (str_contains($request->url(), '/v1/geocode')) {
                $postcodes = ['EX16 0AA', 'EX1 1AA', 'EX2 2BB'];
                $postcode = $postcodes[$geocodeIndex++];

                return Http::response(['items' => [[
                    'resultType' => 'postalCode',
                    'address' => ['postalCode' => $postcode, 'countryCode' => 'GB'],
                    'position' => ['lat' => 50.7, 'lng' => -3.5],
                ]]]);
            }

            return Http::response(['routes' => [['sections' => [
                ['summary' => ['length' => 1609, 'duration' => 600]],
                ['summary' => ['length' => 3218, 'duration' => 1200]],
                ['summary' => ['length' => 4828, 'duration' => 1800]],
            ]]]]);
        });

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('provider_unavailable', $result['overall_status']);
        $this->assertSame('provider_unavailable', $result['failure_detail']['code']);
        Http::assertNothingSent();
    }

    public function test_an_unavailable_here_service_is_a_provider_unavailable_failure(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');

        Http::fake(function (): mixed {
            return Http::response(['error' => 'unavailable'], 503);
        });

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('provider_unavailable', $result['overall_status']);
        $this->assertSame('provider_unavailable', $result['failure_detail']['code']);
        Http::assertSentCount(1);
    }

    public function test_malformed_here_route_data_is_a_provider_response_invalid_failure(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
        $geocodeIndex = 0;

        Http::fake(function (Request $request) use (&$geocodeIndex) {
            if (str_contains($request->url(), '/v1/geocode')) {
                $postcodes = ['EX16 0AA', 'EX1 1AA', 'EX2 2BB'];
                $postcode = $postcodes[$geocodeIndex++];

                return Http::response(['items' => [[
                    'resultType' => 'postalCode',
                    'address' => ['postalCode' => $postcode, 'countryCode' => 'GB'],
                    'position' => ['lat' => 50.7, 'lng' => -3.5],
                ]]]);
            }

            return Http::response(['routes' => [['sections' => [
                ['summary' => ['length' => 1609, 'duration' => 600]],
                ['summary' => ['length' => 3218]],
                ['summary' => ['length' => 4828, 'duration' => 1800]],
            ]]]]);
        });

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('provider_response_invalid', $result['overall_status']);
        $this->assertSame('provider_response_invalid', $result['failure_detail']['code']);
        $this->assertFalse($result['pricing_eligible']);
        Http::assertSentCount(4);
    }

    public function test_more_than_three_route_sections_are_provider_response_invalid(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
        $geocodeIndex = 0;

        Http::fake(function (Request $request) use (&$geocodeIndex) {
            if (str_contains($request->url(), '/v1/geocode')) {
                $postcodes = ['EX16 0AA', 'EX1 1AA', 'EX2 2BB'];
                $postcode = $postcodes[$geocodeIndex++];

                return Http::response(['items' => [[
                    'resultType' => 'postalCode',
                    'address' => ['postalCode' => $postcode, 'countryCode' => 'GB'],
                    'position' => ['lat' => 50.7, 'lng' => -3.5],
                ]]]);
            }

            return Http::response(['routes' => [['sections' => [
                ['summary' => ['length' => 1609, 'duration' => 600]],
                ['summary' => ['length' => 3218, 'duration' => 1200]],
                ['summary' => ['length' => 4828, 'duration' => 1800]],
                ['summary' => ['length' => 6437, 'duration' => 2400]],
            ]]]]);
        });

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('provider_response_invalid', $result['overall_status']);
        $this->assertFalse($result['pricing_eligible']);
        Http::assertSentCount(4);
    }

    public function test_untrusted_geocode_items_are_provider_response_invalid_without_routing(): void
    {
        $invalidItems = [
            ['resultType' => 'postalCode', 'address' => ['postalCode' => 'EX16 0AA'], 'position' => ['lat' => 50.7, 'lng' => -3.5]],
            ['resultType' => 'postalCode', 'address' => ['postalCode' => 'EX16 0AA', 'countryCode' => 'US'], 'position' => ['lat' => 50.7, 'lng' => -3.5]],
            ['resultType' => 'postalCode', 'address' => ['postalCode' => 'EX16 0AA', 'countryCode' => 'GB'], 'position' => ['lat' => 91, 'lng' => -3.5]],
            ['resultType' => 'postalCode', 'address' => ['postalCode' => 'EX16 0AA', 'countryCode' => 'GB'], 'position' => ['lat' => 50.7, 'lng' => -181]],
            ['resultType' => 'postalCode', 'address' => ['countryCode' => 'GB'], 'position' => ['lat' => 50.7, 'lng' => -3.5]],
            ['resultType' => 'postalCode', 'address' => ['postalCode' => 'EX16 0AA', 'countryCode' => 'GB'], 'position' => ['lat' => 'not-a-number', 'lng' => -3.5]],
        ];

        foreach ($invalidItems as $invalidItem) {
            config()->set('services.here.api_key', 'test-key');
            config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
            config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
            $geocodeIndex = 0;

            Http::fake(function (Request $request) use (&$geocodeIndex, $invalidItem) {
                if (str_contains($request->url(), '/v1/geocode')) {
                    $geocodeIndex++;

                    return Http::response(['items' => [$geocodeIndex === 1 ? $invalidItem : $this->validGeocodeItem('EX1 1AA')]]);
                }

                return Http::response(['routes' => [['sections' => $this->threeRouteSections()]]]);
            });

            $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

            $this->assertSame('provider_response_invalid', $result['overall_status']);
            $this->assertFalse($result['pricing_eligible']);
            Http::assertSentCount(1);
        }
    }

    public function test_blank_or_malformed_postcodes_are_invalid_input_before_configuration_or_network_calls(): void
    {
        $invalidInputs = [
            'depot_postcode' => ['', 'not-a-postcode'],
            'pickup_postcode' => ['', 'not-a-postcode'],
            'dropoff_postcode' => ['', 'not-a-postcode'],
        ];

        foreach ($invalidInputs as $field => $values) {
            foreach ($values as $invalidValue) {
                config()->set('services.here.api_key', null);
                Http::fake();
                $postcodes = ['depot_postcode' => 'EX16 0AA', 'pickup_postcode' => 'EX1 1AA', 'dropoff_postcode' => 'EX2 2BB'];
                $postcodes[$field] = $invalidValue;

                $result = $this->resolveHere(
                    $postcodes['depot_postcode'],
                    $postcodes['pickup_postcode'],
                    $postcodes['dropoff_postcode'],
                );

                $this->assertSame('invalid_input', $result['overall_status']);
                $this->assertSame($field, $result['failure_detail']['context']['field']);
                Http::assertNothingSent();
            }
        }
    }

    public function test_a_non_unavailability_client_error_is_provider_response_invalid(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
        Http::fake(fn (): mixed => Http::response(['error' => 'unprocessable'], 422));

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('provider_response_invalid', $result['overall_status']);
        $this->assertSame('provider_response_invalid', $result['failure_detail']['code']);
        Http::assertSentCount(1);
    }

    public function test_a_malformed_geocoding_body_is_provider_response_invalid(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
        Http::fake(fn (): mixed => Http::response(['items' => 'unexpected']));

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('provider_response_invalid', $result['overall_status']);
        $this->assertSame('provider_response_invalid', $result['failure_detail']['code']);
        Http::assertSentCount(1);
    }

    public function test_a_malformed_routing_body_is_provider_response_invalid(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
        $geocodeIndex = 0;

        Http::fake(function (Request $request) use (&$geocodeIndex) {
            if (str_contains($request->url(), '/v1/geocode')) {
                $postcodes = ['EX16 0AA', 'EX1 1AA', 'EX2 2BB'];

                return Http::response(['items' => [$this->validGeocodeItem($postcodes[$geocodeIndex++])]]);
            }

            return Http::response(['routes' => 'unexpected']);
        });

        $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('provider_response_invalid', $result['overall_status']);
        $this->assertSame('provider_response_invalid', $result['failure_detail']['code']);
        Http::assertSentCount(4);
    }

    public function test_postcode_normalisation_warns_while_mismatched_or_ambiguous_locations_require_review(): void
    {
        $outcomes = [
            ['status' => 'resolved_with_warning', 'item' => $this->geocodeItem('postalCode', 'ex160aa')],
            ['status' => 'operator_review_required', 'item' => $this->geocodeItem('postalCode', 'EX9 9ZZ')],
            ['status' => 'operator_review_required', 'item' => $this->geocodeItem('locality', 'EX16 0AA')],
        ];

        foreach ($outcomes as $outcome) {
            config()->set('services.here.api_key', 'test-key');
            config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
            config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
            $geocodeIndex = 0;

            Http::fake(function (Request $request) use (&$geocodeIndex, $outcome) {
                if (str_contains($request->url(), '/v1/geocode')) {
                    $postcodes = ['EX16 0AA', 'EX1 1AA', 'EX2 2BB'];
                    $item = $geocodeIndex === 0 ? $outcome['item'] : $this->validGeocodeItem($postcodes[$geocodeIndex % 3]);
                    $geocodeIndex++;

                    return Http::response(['items' => [$item]]);
                }

                return Http::response(['routes' => [['sections' => $this->threeRouteSections()]]]);
            });

            $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

            $this->assertSame($outcome['status'], $result['overall_status']);
            $this->assertSame($outcome['status'] === 'resolved_with_warning', $result['pricing_eligible']);
            if ($outcome['status'] === 'resolved_with_warning') {
                $this->assertSame('postcode_normalised', $result['warnings'][0]['code']);
            }
            Http::assertSentCount(4);
        }
    }

    public function test_provider_failures_expose_safe_status_and_category_diagnostics(): void
    {
        $httpFailures = [
            [401, 'provider_unavailable', 'authentication'],
            [429, 'provider_unavailable', 'quota'],
            [503, 'provider_unavailable', 'service'],
            [422, 'provider_response_invalid', 'client_error'],
        ];

        $fakeResponse = Http::response(['error' => 'provider failure'], 401);
        Http::fake(function () use (&$fakeResponse): mixed {
            return $fakeResponse;
        });

        foreach ($httpFailures as [$statusCode, $status, $category]) {
            config()->set('services.here.api_key', 'test-key');
            config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
            config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
            $fakeResponse = Http::response(['error' => 'provider failure'], $statusCode);

            $result = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

            $this->assertSame($status, $result['overall_status']);
            $this->assertSame($category, $result['failure_detail']['context']['error_category'] ?? null);
            $this->assertSame($statusCode, $result['failure_detail']['context']['http_status'] ?? null);
            $this->assertSame($category, $result['provider_metadata']['error_category'] ?? null);
            $this->assertSame($statusCode, $result['provider_metadata']['http_status'] ?? null);
        }

        config()->set('services.here.api_key', 'test-key');
        $fakeResponse = Http::failedConnection();
        $connectionFailure = $this->resolveHere('EX16 0AA', 'EX1 1AA', 'EX2 2BB');

        $this->assertSame('provider_unavailable', $connectionFailure['overall_status']);
        $this->assertSame('connection', $connectionFailure['failure_detail']['context']['error_category'] ?? null);
        $this->assertSame('connection', $connectionFailure['provider_metadata']['error_category'] ?? null);
    }

    private function resolveHere(string $depotPostcode, string $pickupPostcode, string $dropoffPostcode): array
    {
        $request = new RouteResolutionRequest(1, $depotPostcode, $pickupPostcode, $dropoffPostcode, now(), []);

        return app(HereRouteDistanceAdapter::class)->resolve($request);
    }

    private function validGeocodeItem(string $postcode): array
    {
        return [
            'resultType' => 'postalCode',
            'address' => ['postalCode' => $postcode, 'countryCode' => 'GB'],
            'position' => ['lat' => 50.7, 'lng' => -3.5],
        ];
    }

    private function geocodeItem(string $resultType, string $postcode): array
    {
        return [
            'resultType' => $resultType,
            'address' => ['postalCode' => $postcode, 'countryCode' => 'GB'],
            'position' => ['lat' => 50.7, 'lng' => -3.5],
        ];
    }

    private function threeRouteSections(): array
    {
        return [
            ['summary' => ['length' => 1609, 'duration' => 600]],
            ['summary' => ['length' => 3218, 'duration' => 1200]],
            ['summary' => ['length' => 4828, 'duration' => 1800]],
        ];
    }
}
