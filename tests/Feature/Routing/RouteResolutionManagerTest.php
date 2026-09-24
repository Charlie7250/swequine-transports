<?php

namespace Tests\Feature\Routing;

use App\Models\RateSetting;
use App\Models\TransportEnquiry;
use App\Services\Routing\RouteDistanceAdapter;
use App\Services\Routing\RouteResolutionManager;
use App\Services\Routing\RouteResolutionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RouteResolutionManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_route_resolution_manager_is_not_available_before_it_is_created(): void
    {
        $this->assertTrue(class_exists('App\\Services\\Routing\\RouteResolutionManager'));
    }

    public function test_a_successful_route_is_persisted_with_three_ordered_legs(): void
    {
        $this->configureHere();
        $this->createActiveRateSetting();
        $enquiry = $this->createEnquiry();
        $this->fakeSuccessfulHereRequests();

        $resolution = app(RouteResolutionManager::class)->resolve($enquiry, 'horsebox');

        $this->assertSame('resolved', $resolution->overall_status);
        $this->assertTrue($resolution->pricing_eligible);
        $this->assertNotNull($resolution->attempted_at);
        $this->assertNotNull($resolution->resolved_at);
        $this->assertCount(3, $resolution->legs);
        $this->assertSame(['depot_to_pickup', 'pickup_to_drop_off', 'drop_off_to_depot'], $resolution->legs->pluck('leg_type')->all());
        $this->assertDatabaseHas('route_resolutions', ['id' => $resolution->id, 'overall_status' => 'resolved']);
    }

    public function test_a_failed_route_is_persisted_without_a_resolved_timestamp(): void
    {
        $this->configureHere();
        $this->createActiveRateSetting();
        $enquiry = $this->createEnquiry();
        Http::fake(fn (): mixed => Http::response(['error' => 'unavailable'], 503));

        $resolution = app(RouteResolutionManager::class)->resolve($enquiry, 'horsebox');

        $this->assertSame('provider_unavailable', $resolution->overall_status);
        $this->assertFalse($resolution->pricing_eligible);
        $this->assertNotNull($resolution->attempted_at);
        $this->assertNull($resolution->resolved_at);
        $this->assertCount(3, $resolution->legs);
        $this->assertSame(['provider_unavailable', 'provider_unavailable', 'provider_unavailable'], $resolution->legs->pluck('status')->all());
    }

    public function test_failure_details_are_persisted_as_structured_json_for_the_resolution_and_legs(): void
    {
        $this->configureHere();
        $this->createActiveRateSetting();
        $enquiry = $this->createEnquiry();
        Http::fake(fn (): mixed => Http::response(['error' => 'unavailable'], 503));

        $resolution = app(RouteResolutionManager::class)->resolve($enquiry, 'horsebox')->fresh('legs');

        $this->assertSame([
            'code' => 'provider_unavailable',
            'message' => 'HERE geocoding was unavailable.',
            'context' => ['stage' => 'geocoding', 'http_status' => 503, 'error_category' => 'service'],
        ], $resolution->failure_detail);
        $this->assertSame('provider_unavailable', $resolution->legs[0]->failure_detail['code']);
        $this->assertIsArray($resolution->failure_detail);
        $this->assertIsArray($resolution->legs[0]->failure_detail);
    }

    public function test_provider_diagnostics_are_persisted_without_response_content_or_credentials(): void
    {
        $this->configureHere();
        $this->createActiveRateSetting();
        $enquiry = $this->createEnquiry();
        Http::fake(fn (): mixed => Http::response(['error' => 'unauthorised'], 401));

        $resolution = app(RouteResolutionManager::class)->resolve($enquiry)->fresh();

        $this->assertSame('authentication', $resolution->failure_detail['context']['error_category'] ?? null);
        $this->assertSame(401, $resolution->failure_detail['context']['http_status'] ?? null);
        $this->assertSame('authentication', $resolution->provider_metadata['error_category'] ?? null);
        $this->assertSame(401, $resolution->provider_metadata['http_status'] ?? null);
    }

    public function test_api_keys_and_raw_provider_payloads_are_excluded_from_persisted_route_records(): void
    {
        Http::fake();
        $this->createActiveRateSetting();
        $enquiry = $this->createEnquiry();
        $manager = new RouteResolutionManager($this->unsafeProviderResultAdapter());

        $resolution = $manager->resolve($enquiry, 'horsebox')->fresh('legs');
        $persistedData = json_encode($resolution->toArray());

        foreach ([
            'secret-test-key',
            'raw-provider-payload',
            'nested-raw-response',
            'response-body',
            'authorisation-token',
            'access-token',
            'client-secret',
            'header-secret',
        ] as $secret) {
            $this->assertStringNotContainsString($secret, $persistedData);
        }
        $this->assertSame('provider_response_invalid', $resolution->failure_detail['code']);
        $this->assertSame('provider_response_invalid', $resolution->legs[0]->failure_detail['code']);
    }

    public function test_resolving_an_enquiry_twice_preserves_two_independent_route_attempts(): void
    {
        $this->assertSame(1, (new \ReflectionMethod(RouteResolutionManager::class, 'resolve'))->getNumberOfParameters());
        $this->configureHere();
        $this->createActiveRateSetting();
        $enquiry = $this->createEnquiry();
        $this->fakeSuccessfulHereRequests();

        $firstResolution = app(RouteResolutionManager::class)->resolve($enquiry, 'horsebox');
        $secondResolution = app(RouteResolutionManager::class)->resolve($enquiry, 'horsebox');

        $this->assertNotSame($firstResolution->id, $secondResolution->id);
        $this->assertDatabaseCount('route_resolutions', 2);
        $this->assertDatabaseCount('route_resolution_legs', 6);
        $this->assertSame([$firstResolution->id, $secondResolution->id], $enquiry->routeResolutions()->pluck('id')->all());
    }

    public function test_a_route_attempt_persists_request_response_and_latency_timing(): void
    {
        $this->configureHere();
        $this->createActiveRateSetting();
        $enquiry = $this->createEnquiry();
        $this->fakeSuccessfulHereRequests();

        $resolution = app(RouteResolutionManager::class)->resolve($enquiry, 'horsebox')->fresh();

        $this->assertNotNull($resolution->request_started_at);
        $this->assertNotNull($resolution->response_received_at);
        $this->assertIsInt($resolution->latency_milliseconds);
        $this->assertGreaterThanOrEqual(0, $resolution->latency_milliseconds);
        $this->assertNotNull($resolution->attempted_at);
    }

    public function test_the_here_profile_is_fixed_to_car_when_the_prior_boundary_receives_a_non_car_value(): void
    {
        $this->assertSame(1, (new \ReflectionMethod(RouteResolutionManager::class, 'resolve'))->getNumberOfParameters());
        $this->configureHere();
        $this->createActiveRateSetting();
        $enquiry = $this->createEnquiry();
        $this->fakeSuccessfulHereRequests();

        $resolution = app(RouteResolutionManager::class)->resolve($enquiry, 'horsebox');

        $this->assertSame('car', $resolution->route_profile);
        $this->assertSame('car', $resolution->provider_metadata['transport_mode']);
    }

    public function test_pending_route_attempt_identity_is_passed_to_the_adapter_and_preserved_on_retry(): void
    {
        Http::fake();
        $this->createActiveRateSetting();
        $enquiry = $this->createEnquiry();
        $adapter = new class($this->resolvedProviderResult()) implements RouteDistanceAdapter
        {
            public array $requests = [];

            public function __construct(private array $result) {}

            public function resolve(RouteResolutionRequest $request): array
            {
                $this->requests[] = [
                    'resolution_id' => $request->resolutionId,
                    'request_started_at' => $request->requestStartedAt,
                    'request_context' => $request->requestContext,
                ];

                return $this->result;
            }
        };
        $manager = new RouteResolutionManager($adapter);

        $firstResolution = $manager->resolve($enquiry);
        $secondResolution = $manager->resolve($enquiry);

        $this->assertSame($firstResolution->id, $adapter->requests[0]['resolution_id'] ?? null);
        $this->assertSame($secondResolution->id, $adapter->requests[1]['resolution_id'] ?? null);
        $this->assertNotNull($adapter->requests[0]['request_started_at'] ?? null);
        $this->assertSame(['transport_enquiry_id' => $enquiry->id], $adapter->requests[0]['request_context'] ?? null);
        $this->assertSame(['transport_enquiry_id' => $enquiry->id], $firstResolution->fresh()->request_context);
        $this->assertDatabaseHas('route_resolutions', ['id' => $firstResolution->id, 'transport_enquiry_id' => $enquiry->id]);
        $this->assertDatabaseHas('route_resolutions', ['id' => $secondResolution->id, 'transport_enquiry_id' => $enquiry->id]);
        $this->assertDatabaseCount('route_resolutions', 2);
        $this->assertDatabaseCount('route_resolution_legs', 6);
    }

    private function configureHere(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
    }

    private function createActiveRateSetting(): void
    {
        RateSetting::query()->create([
            'name' => 'Default',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => 10,
            'litres_per_gallon' => 4.54609,
            'maintenance_per_mile' => 0.1,
            'unloaded_add_on_per_mile' => 0.1,
            'loaded_add_on_per_mile' => 0.1,
            'one_horse_multiplier' => '1.500000',
            'is_active' => true,
        ]);
    }

    private function createEnquiry(): TransportEnquiry
    {
        return TransportEnquiry::query()->create([
            'source' => 'private_enquiry',
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'EX2 2BB',
        ]);
    }

    private function fakeSuccessfulHereRequests(): void
    {
        $geocodeIndex = 0;
        Http::fake(function (Request $request) use (&$geocodeIndex) {
            if (str_contains($request->url(), '/v1/geocode')) {
                $postcodes = ['EX16 0AA', 'EX1 1AA', 'EX2 2BB'];
                $postcode = $postcodes[$geocodeIndex++ % 3];

                return Http::response(['items' => [[
                    'resultType' => 'postalCode',
                    'address' => ['postalCode' => $postcode, 'countryCode' => 'GB'],
                    'position' => ['lat' => 50.7, 'lng' => -3.5],
                ]]]);
            }

            return Http::response(['routes' => [['id' => 'route-123', 'sections' => [
                ['summary' => ['length' => 1609, 'duration' => 600]],
                ['summary' => ['length' => 3218, 'duration' => 1200]],
                ['summary' => ['length' => 4828, 'duration' => 1800]],
            ]]]]);
        });
    }

    private function unsafeProviderResultAdapter(): RouteDistanceAdapter
    {
        return new class implements RouteDistanceAdapter
        {
            public function resolve(RouteResolutionRequest $request): array
            {
                $failure = [
                    'code' => 'provider_response_invalid',
                    'message' => 'HERE routing returned an unexpected response.',
                    'context' => [
                        'stage' => 'routing',
                        'raw_payload' => 'raw-provider-payload',
                        'raw_response' => 'nested-raw-response',
                        'response_body' => 'response-body',
                        'authorization' => 'authorisation-token',
                        'token' => 'access-token',
                        'client_secret' => 'client-secret',
                        'headers' => ['X-Provider-Key' => 'header-secret'],
                    ],
                ];

                return [
                    'provider' => 'here',
                    'provider_product' => 'geocoding_and_routing_v8',
                    'request_id' => null,
                    'route_profile' => RouteResolutionRequest::ROUTE_PROFILE,
                    'overall_status' => 'provider_response_invalid',
                    'operator_action_required' => false,
                    'pricing_eligible' => false,
                    'raw_input_snapshot' => [
                        'depot_postcode' => $request->depotPostcode,
                        'pickup_postcode' => $request->pickupPostcode,
                        'dropoff_postcode' => $request->dropoffPostcode,
                    ],
                    'normalisation_metadata' => [],
                    'provider_metadata' => ['api_key' => 'secret-test-key', 'raw_response' => 'nested-raw-response'],
                    'warnings' => [],
                    'legs' => $this->failureLegs($request->depotPostcode, $request->pickupPostcode, $request->dropoffPostcode, $failure),
                    'failure_detail' => $failure,
                ];
            }

            private function failureLegs(string $depotPostcode, string $pickupPostcode, string $dropoffPostcode, array $failure): array
            {
                $postcodes = [$depotPostcode, $pickupPostcode, $dropoffPostcode];
                $legTypes = ['depot_to_pickup', 'pickup_to_drop_off', 'drop_off_to_depot'];

                return array_map(function (string $legType, int $index) use ($postcodes, $failure): array {
                    return [
                        'sequence' => $index + 1,
                        'leg_type' => $legType,
                        'origin_input' => $postcodes[$index],
                        'destination_input' => $postcodes[$index + 1] ?? $postcodes[0],
                        'resolved_origin_metadata' => null,
                        'resolved_destination_metadata' => null,
                        'status' => 'provider_response_invalid',
                        'distance_metres' => null,
                        'quoted_miles' => null,
                        'duration_seconds' => null,
                        'provider_route_id' => null,
                        'warnings' => [],
                        'failure_detail' => $failure,
                    ];
                }, $legTypes, array_keys($legTypes));
            }
        };
    }

    private function resolvedProviderResult(): array
    {
        $postcodes = ['EX16 0AA', 'EX1 1AA', 'EX2 2BB'];

        return [
            'provider' => 'here',
            'provider_product' => 'geocoding_and_routing_v8',
            'request_id' => null,
            'route_profile' => 'car',
            'overall_status' => 'resolved',
            'operator_action_required' => false,
            'pricing_eligible' => true,
            'raw_input_snapshot' => ['depot_postcode' => $postcodes[0], 'pickup_postcode' => $postcodes[1], 'dropoff_postcode' => $postcodes[2]],
            'normalisation_metadata' => [],
            'provider_metadata' => ['routing_version' => 'v8', 'transport_mode' => 'car'],
            'warnings' => [],
            'legs' => $this->resolvedProviderLegs($postcodes),
            'failure_detail' => null,
        ];
    }

    private function resolvedProviderLegs(array $postcodes): array
    {
        $legTypes = ['depot_to_pickup', 'pickup_to_drop_off', 'drop_off_to_depot'];

        return array_map(function (string $legType, int $index) use ($postcodes): array {
            return [
                'sequence' => $index + 1,
                'leg_type' => $legType,
                'origin_input' => $postcodes[$index],
                'destination_input' => $postcodes[$index + 1] ?? $postcodes[0],
                'resolved_origin_metadata' => null,
                'resolved_destination_metadata' => null,
                'status' => 'resolved',
                'distance_metres' => 1609,
                'quoted_miles' => 1,
                'duration_seconds' => 600,
                'provider_route_id' => 'route-123',
                'warnings' => [],
                'failure_detail' => null,
            ];
        }, $legTypes, array_keys($legTypes));
    }
}
