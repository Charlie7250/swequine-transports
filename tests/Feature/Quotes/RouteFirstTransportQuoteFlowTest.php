<?php

namespace Tests\Feature\Quotes;

use App\Models\JobRevision;
use App\Models\RateSetting;
use App\Models\RouteResolution;
use App\Models\RouteResolutionLeg;
use App\Models\RouteResolutionReviewDecision;
use App\Models\TransportEnquiry;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RouteFirstTransportQuoteFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_start_a_transport_enquiry_without_manual_mileage_or_total_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/quotes/create')
            ->assertOk()
            ->assertSeeText('New transport enquiry')
            ->assertSeeText('Loading practice is separate')
            ->assertDontSee('route_legs')
            ->assertDontSee('manual_final_total');
    }

    public function test_transport_enquiry_form_contains_a_resolve_submission_token(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/quotes/create');

        $this->assertSame(1, preg_match('/name="submission_token"[^>]*value="[^"]+"/', $response->getContent()));
    }

    public function test_staff_cannot_create_a_manual_quote_through_the_quotes_endpoint(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();

        $this->post('/quotes', $this->manualWorkspacePayload())
            ->assertNotFound();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('job_revisions', 0);
        $this->assertDatabaseCount('route_legs', 0);
    }

    public function test_staff_can_resolve_a_complete_transport_enquiry_and_review_the_persisted_route(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $this->configureHere();
        $this->fakeSuccessfulHereRequests();

        $response = $this->post('/transport-enquiries/resolve', array_merge($this->completeEnquiryPayload(), [
            'submission_token' => $this->resolveSubmissionToken(),
        ]));

        $response->assertRedirect();

        $resolution = RouteResolution::query()->with('transportEnquiry')->firstOrFail();

        $response->assertRedirect("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}");
        $this->assertSame('Amber Vale Eventing', $resolution->transportEnquiry->customer_name);
        $this->assertSame('resolved', $resolution->overall_status);
        $this->assertCount(3, $resolution->legs()->get());
    }

    public function test_repeated_resolve_submission_token_reuses_the_original_route_resolution(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $this->configureHere();
        $this->fakeSuccessfulHereRequests();
        $submissionToken = $this->resolveSubmissionToken();
        $payload = array_merge($this->completeEnquiryPayload(), [
            'submission_token' => $submissionToken,
        ]);

        $this->post('/transport-enquiries/resolve', $payload)
            ->assertRedirect();

        $resolution = RouteResolution::query()->firstOrFail();
        $routeReviewPath = "/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}";

        $this->post('/transport-enquiries/resolve', $payload)
            ->assertRedirect($routeReviewPath);

        $this->assertDatabaseCount('transport_enquiries', 1);
        $this->assertDatabaseCount('route_resolutions', 1);
        Http::assertSentCount(4);
    }

    public function test_staff_can_review_the_automatic_route_with_depot_and_provider_evidence(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution();

        $this->get("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}")
            ->assertOk()
            ->assertSeeText('Route calculated from the entered postcodes.')
            ->assertSeeText('EX16 0AA')
            ->assertSeeText('Depot to pickup')
            ->assertSeeText('Pickup to drop-off')
            ->assertSeeText('Drop-off to depot')
            ->assertSeeText('HERE')
            ->assertSeeText('Use this route for quote')
            ->assertDontSee('manual_final_total')
            ->assertDontSee('route_legs');
    }

    public function test_staff_can_create_a_priced_draft_quote_from_an_accepted_automatic_route(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution();

        $response = $this->post("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}/accept");

        $response->assertRedirect();

        $revision = JobRevision::query()->with(['job.customer', 'routeLegs'])->firstOrFail();
        $enquiry = $resolution->transportEnquiry()->firstOrFail();
        $routeLegs = $revision->routeLegs->sortBy('sequence')->values();

        $response->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}");
        $this->assertSame($resolution->id, $revision->route_resolution_id);
        $this->assertSame('draft_quote', $enquiry->status);
        $this->assertSame($revision->job_id, $enquiry->quote_job_id);
        $this->assertSame('Amber Vale Eventing', $revision->job->customer->name);
        $this->assertSame('harriet@example.test', $revision->job->customer->email);
        $this->assertSame('07700 900123', $revision->job->customer->phone);
        $this->assertSame('218.97', $revision->engine_total);
        $this->assertSame('218.97', $revision->final_total);
        $this->assertSame(['depot_to_pickup', 'pickup_to_dropoff', 'dropoff_to_depot'], $routeLegs->pluck('label')->all());
        $this->assertSame([10, 90, 96], $routeLegs->pluck('miles')->all());
        $this->assertSame([null, null, null], $routeLegs->pluck('manual_miles')->all());
    }

    public function test_route_first_draft_shows_automatic_route_evidence_without_editable_manual_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution();

        $this->post("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}/accept")
            ->assertRedirect();

        $revision = JobRevision::query()->firstOrFail();

        $this->get("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->assertOk()
            ->assertSeeText('Automatic route')
            ->assertSeeText('HERE')
            ->assertSeeText('Engine total')
            ->assertDontSee('name="manual_final_total"', false)
            ->assertDontSee('name="route_legs[0][miles]"', false);
    }

    public function test_route_first_draft_shows_the_issue_quote_action_without_manual_mileage_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution();

        $this->post("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}/accept")
            ->assertRedirect();

        $revision = JobRevision::query()->firstOrFail();

        $this->get("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->assertOk()
            ->assertSeeText('Issue quote')
            ->assertDontSee('name="route_legs[0][miles]"', false);
    }

    public function test_route_first_draft_can_be_issued(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution();

        $this->post("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}/accept")
            ->assertRedirect();

        $revision = JobRevision::query()->firstOrFail();
        $revisionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}";

        $this->from($revisionPath)
            ->post("{$revisionPath}/issue", $this->issueConfirmation($revision))
            ->assertRedirect("{$revisionPath}/issued");

        $this->assertSame('quoted', $revision->job()->firstOrFail()->status);
    }

    public function test_enquiry_validation_preserves_input_and_uses_route_first_messages_without_manual_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $submissionToken = $this->resolveSubmissionToken();
        $response = $this->from('/quotes/create')->post('/transport-enquiries/resolve', [
            'submission_token' => $submissionToken,
            'source' => '',
            'customer_name' => '',
            'email' => '',
            'phone' => '',
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => '',
            'horse_count' => 0,
            'requested_date' => '',
            'date_to_be_arranged' => false,
            'special_constraints' => '',
            'special_constraints_acknowledged' => false,
        ]);

        $response
            ->assertRedirect('/quotes/create')
            ->assertSessionHasErrors([
                'source' => 'Select the enquiry source before issuing a quote.',
                'customer_name' => "Enter the customer's name before issuing a quote.",
                'email' => 'Add at least one way to contact the customer before issuing a quote.',
                'dropoff_postcode' => 'Enter the drop-off postcode to calculate the pickup-to-drop-off and drop-off-to-depot legs.',
                'horse_count' => 'Enter the number of horses for this transport quote.',
                'requested_date' => 'Enter the requested date or select date to be arranged before issuing a quote.',
                'special_constraints_acknowledged' => 'Confirm whether any special transport constraints are known before issuing a quote.',
            ])
            ->assertSessionHasInput('pickup_postcode', 'EX1 1AA');

        $this->assertDatabaseCount('transport_enquiries', 0);
    }

    public function test_a_route_warning_can_be_used_to_create_a_draft_quote(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution(
            status: 'resolved_with_warning',
            warnings: [[
                'code' => 'postcode_normalised',
                'message' => 'HERE normalised the postcode.',
                'context' => ['field' => 'pickup_postcode'],
            ]],
        );

        $this->get("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}")
            ->assertOk()
            ->assertSeeText('Route calculated, but review the highlighted detail before continuing.')
            ->assertSeeText('HERE normalised the postcode.')
            ->assertSeeText('Use this route for quote');

        $this->post("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}/accept")
            ->assertRedirect();

        $this->assertDatabaseCount('job_revisions', 1);
    }

    public function test_review_required_routes_can_be_accepted_and_hard_failures_offer_the_exception_path(): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createActivePricingContext();

        $reviewRequiredResolution = $this->createResolvedRouteResolution('operator_review_required', false);
        $reviewRequiredPath = "/transport-enquiries/{$reviewRequiredResolution->transport_enquiry_id}/route-resolutions/{$reviewRequiredResolution->id}";

        $this->get($reviewRequiredPath)
            ->assertOk()
            ->assertSeeText('This route needs your review before it can be used for pricing.')
            ->assertSeeText('Accept reviewed route for quote')
            ->assertSeeText('Handle route exception');

        $this->post("{$reviewRequiredPath}/accept")->assertRedirect();

        $providerFailureResolution = $this->createResolvedRouteResolution('provider_unavailable', false);
        $providerFailurePath = "/transport-enquiries/{$providerFailureResolution->transport_enquiry_id}/route-resolutions/{$providerFailureResolution->id}";

        $this->get($providerFailurePath)
            ->assertOk()
            ->assertSeeText('Route lookup is temporarily unavailable. Your enquiry has been saved.')
            ->assertSeeText('Handle route exception')
            ->assertDontSee('Use this route for quote');

        $this->from($providerFailurePath)
            ->post("{$providerFailurePath}/accept")
            ->assertRedirect($providerFailurePath)
            ->assertSessionHasErrors('route');

        $this->assertDatabaseCount('job_revisions', 1);
    }

    public function test_only_authorised_operators_can_accept_review_required_routes_and_their_decision_is_audited(): void
    {
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution('operator_review_required', false);
        $acceptPath = "/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}/accept";

        $this->actingAs(User::factory()->create());

        $this->post($acceptPath)->assertForbidden();

        $operator = User::factory()->create(['can_manage_quote_exceptions' => true]);
        $this->actingAs($operator);

        $this->post($acceptPath)->assertRedirect();

        $decision = RouteResolutionReviewDecision::query()->firstOrFail();
        $revision = JobRevision::query()->firstOrFail();

        $this->assertSame($resolution->id, $decision->route_resolution_id);
        $this->assertSame($operator->id, $decision->actor_id);
        $this->assertSame('accepted_for_pricing', $decision->decision);
        $this->assertNotNull($decision->recorded_at);
        $this->assertSame($resolution->id, $revision->route_resolution_id);
    }

    public function test_unavailable_active_pricing_blocks_route_acceptance_without_creating_a_draft(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActiveRateSetting();
        $resolution = $this->createResolvedRouteResolution();
        $routeReviewPath = "/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}";

        $this->get($routeReviewPath)
            ->assertOk()
            ->assertSeeText('This quote cannot be priced because the active fuel or rate context is unavailable. Ask the responsible staff member to update it.')
            ->assertDontSee('Use this route for quote');

        $this->from($routeReviewPath)
            ->post("{$routeReviewPath}/accept")
            ->assertRedirect($routeReviewPath)
            ->assertSessionHasErrors([
                'pricing' => 'This quote cannot be priced because the active fuel or rate context is unavailable. Ask the responsible staff member to update it.',
            ]);

        $this->assertDatabaseCount('job_revisions', 0);
    }

    public function test_unsupported_horse_counts_remain_enquiries_without_creating_priced_revisions(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution();
        $resolution->transportEnquiry()->update(['horse_count' => 3]);
        $routeReviewPath = "/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}";

        $this->from($routeReviewPath)
            ->post("{$routeReviewPath}/accept")
            ->assertRedirect($routeReviewPath)
            ->assertSessionHasErrors([
                'pricing' => 'Automatic transport pricing supports one or two horses. Counts above two require manual review.',
            ]);

        $this->assertDatabaseCount('transport_enquiries', 1);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('job_revisions', 0);
        $this->assertDatabaseCount('route_legs', 0);
        $this->assertDatabaseCount('quote_exception_audits', 0);
        $this->assertNull($resolution->transportEnquiry->fresh()->quote_job_id);
    }

    public function test_route_review_directs_unsupported_horse_counts_to_manual_review(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution();
        $resolution->transportEnquiry()->update(['horse_count' => 3]);

        $this->get("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}")
            ->assertOk()
            ->assertSeeText('Automatic transport pricing supports one or two horses. Counts above two require manual review.')
            ->assertDontSee('Use this route for quote');
    }

    public function test_repeating_route_acceptance_returns_the_existing_draft_without_creating_a_duplicate(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution();
        $acceptPath = "/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}/accept";

        $this->post($acceptPath)->assertRedirect();

        $firstRevision = JobRevision::query()->firstOrFail();

        $this->post($acceptPath)
            ->assertRedirect("/jobs/{$firstRevision->job_id}/revisions/{$firstRevision->id}");

        $enquiry = $resolution->transportEnquiry()->firstOrFail();

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('job_revisions', 1);
        $this->assertSame($firstRevision->job_id, $enquiry->quote_job_id);
    }

    public function test_a_provider_failure_is_persisted_and_returns_to_a_non_acceptable_route_review(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $this->configureHere();
        Http::fake(fn (): mixed => Http::response(['error' => 'unavailable'], 503));

        $response = $this->post('/transport-enquiries/resolve', array_merge($this->completeEnquiryPayload(), [
            'submission_token' => $this->resolveSubmissionToken(),
        ]));

        $response->assertRedirect();

        $resolution = RouteResolution::query()->with('transportEnquiry')->firstOrFail();
        $routeReviewPath = "/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}";

        $response->assertRedirect($routeReviewPath);
        $this->assertSame('provider_unavailable', $resolution->overall_status);
        $this->assertFalse($resolution->pricing_eligible);
        $this->assertCount(3, $resolution->legs()->get());

        $this->get($routeReviewPath)
            ->assertOk()
            ->assertSeeText('Route lookup is temporarily unavailable. Your enquiry has been saved.')
            ->assertDontSee('Use this route for quote');
    }

    public function test_route_first_drafts_reject_manual_workspace_updates(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution();

        $this->post("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}/accept")
            ->assertRedirect();

        $revision = JobRevision::query()->firstOrFail();
        $revisionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}";

        $this->from($revisionPath)
            ->patch($revisionPath, $this->manualWorkspacePayload())
            ->assertRedirect($revisionPath)
            ->assertSessionHasErrors('route');

        $this->assertDatabaseCount('job_revisions', 1);
        $this->assertSame($resolution->id, $revision->fresh()->route_resolution_id);
    }

    public function test_route_review_treats_missing_warnings_as_no_warnings(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $resolution = $this->createResolvedRouteResolution();
        $resolution->forceFill(['warnings' => null])->save();

        $this->get("/transport-enquiries/{$resolution->transport_enquiry_id}/route-resolutions/{$resolution->id}")
            ->assertOk()
            ->assertSeeText('Use this route for quote');
    }

    private function completeEnquiryPayload(): array
    {
        return [
            'source' => 'direct_contact',
            'customer_name' => 'Amber Vale Eventing',
            'email' => 'harriet@example.test',
            'phone' => '07700 900123',
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
            'horse_count' => 2,
            'requested_date' => '2026-09-01',
            'date_to_be_arranged' => false,
            'special_constraints' => 'Travelling with tack trunk',
            'special_constraints_acknowledged' => true,
        ];
    }

    private function resolveSubmissionToken(): string
    {
        $response = $this->get('/quotes/create');
        $matched = preg_match('/name="submission_token"[^>]*value="([^"]+)"/', $response->getContent(), $matches);

        $this->assertSame(1, $matched);

        return $matches[1];
    }

    private function manualWorkspacePayload(): array
    {
        return [
            'customer_name' => 'Amber Vale Eventing',
            'customer_contact_name' => 'Harriet Vale',
            'customer_email' => 'harriet@example.test',
            'customer_phone' => '07700 900123',
            'customer_postcode' => 'EX1 1AA',
            'customer_notes' => 'Manual request should be rejected',
            'horse_count' => 2,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
            'revision_notes' => 'Manual request should be rejected',
            'manual_final_total' => '',
            'manual_final_total_reason' => '',
            'route_legs' => [
                ['miles' => 12],
                ['miles' => 91],
                ['miles' => 97],
            ],
        ];
    }

    private function issueConfirmation(JobRevision $revision): array
    {
        return [
            'issue_confirmation' => '1',
            'confirmed_revision_id' => $revision->id,
        ];
    }

    private function createActivePricingContext(): void
    {
        WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-17',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createActiveRateSetting();
    }

    private function createActiveRateSetting(): void
    {
        RateSetting::query()->create([
            'name' => 'Initial internal rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.150000',
            'is_active' => true,
            'effective_from' => '2026-08-17',
        ]);
    }

    private function createResolvedRouteResolution(
        string $status = 'resolved',
        bool $pricingEligible = true,
        array $warnings = [],
    ): RouteResolution {
        $enquiry = TransportEnquiry::query()->create(array_merge(
            $this->completeEnquiryPayload(),
            ['status' => 'draft'],
        ));
        $resolution = RouteResolution::query()->create([
            'transport_enquiry_id' => $enquiry->id,
            'provider' => 'here',
            'provider_product' => 'geocoding_and_routing_v8',
            'request_id' => 'route-123',
            'route_profile' => 'car',
            'overall_status' => $status,
            'operator_action_required' => $status === 'operator_review_required',
            'pricing_eligible' => $pricingEligible,
            'raw_input_snapshot' => [
                'depot_postcode' => 'EX16 0AA',
                'pickup_postcode' => 'EX1 1AA',
                'dropoff_postcode' => 'TA1 1AA',
            ],
            'request_context' => ['transport_enquiry_id' => $enquiry->id],
            'normalisation_metadata' => [],
            'provider_metadata' => ['routing_version' => 'v8', 'transport_mode' => 'car'],
            'warnings' => $warnings,
            'resolved_at' => now(),
            'attempted_at' => now(),
            'request_started_at' => now(),
            'response_received_at' => now(),
            'latency_milliseconds' => 100,
        ]);

        foreach ([
            ['depot_to_pickup', 'EX16 0AA', 'EX1 1AA', 10],
            ['pickup_to_drop_off', 'EX1 1AA', 'TA1 1AA', 90],
            ['drop_off_to_depot', 'TA1 1AA', 'EX16 0AA', 96],
        ] as $index => [$legType, $origin, $destination, $miles]) {
            RouteResolutionLeg::query()->create([
                'route_resolution_id' => $resolution->id,
                'sequence' => $index + 1,
                'leg_type' => $legType,
                'origin_input' => $origin,
                'destination_input' => $destination,
                'status' => 'resolved',
                'distance_metres' => $miles * 1609,
                'quoted_miles' => $miles,
                'duration_seconds' => 600,
                'provider_route_id' => 'route-123',
                'warnings' => [],
            ]);
        }

        return $resolution;
    }

    private function configureHere(): void
    {
        config()->set('services.here.api_key', 'test-key');
        config()->set('services.here.geocoding_url', 'https://geocode.search.hereapi.com/v1/geocode');
        config()->set('services.here.routing_url', 'https://router.hereapi.com/v8/routes');
    }

    private function fakeSuccessfulHereRequests(): void
    {
        $geocodeIndex = 0;

        Http::fake(function (Request $request) use (&$geocodeIndex) {
            if (str_contains($request->url(), '/v1/geocode')) {
                $postcodes = ['EX16 0AA', 'EX1 1AA', 'TA1 1AA'];
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
}
