<?php

namespace Tests\Feature\Quotes;

use App\Models\Customer;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\RateSetting;
use App\Models\RouteLeg;
use App\Models\RouteResolution;
use App\Models\RouteResolutionLeg;
use App\Models\RouteResolutionReviewDecision;
use App\Models\TransportEnquiry;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use App\Services\Quotes\IssuedQuoteChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssuedQuoteOutputTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_first_draft_shows_the_issue_checklist_and_rejects_unconfirmed_or_mismatched_issue_requests(): void
    {
        $this->actingAs(User::factory()->create());
        $revision = $this->createRouteFirstDraft();
        $revisionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}";
        $issuePath = "{$revisionPath}/issue";

        $this->get($revisionPath)
            ->assertOk()
            ->assertSeeText('Issue checklist')
            ->assertSeeText('Customer contact')
            ->assertSeeText('Enquiry source')
            ->assertSeeText('Requested date or date to be arranged')
            ->assertSeeText('Constraints acknowledged')
            ->assertSeeText('Active fuel and rate context')
            ->assertSeeText('Complete three-leg route')
            ->assertSeeText('Quote review confirmation');

        $this->from($revisionPath)
            ->post($issuePath, [
                'confirmed_revision_id' => $revision->id,
            ])
            ->assertRedirect($revisionPath)
            ->assertSessionHasErrors('issue_confirmation');

        $this->from($revisionPath)
            ->post($issuePath, [
                'issue_confirmation' => '1',
                'confirmed_revision_id' => $revision->id + 1,
            ])
            ->assertRedirect($revisionPath)
            ->assertSessionHasErrors('confirmed_revision_id');

        $this->assertNull($revision->fresh()->issued_at);
        $this->assertSame('draft', $revision->job()->value('status'));
    }

    public function test_route_first_draft_rejects_issue_when_a_required_enquiry_item_is_missing(): void
    {
        $this->actingAs(User::factory()->create());
        $revision = $this->createRouteFirstDraft();
        $revisionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}";

        $revision->routeResolution->transportEnquiry()->update(['source' => null]);

        $this->from($revisionPath)
            ->post("{$revisionPath}/issue", $this->issueConfirmation($revision))
            ->assertRedirect($revisionPath)
            ->assertSessionHasErrors('issue_checklist');

        $this->assertNull($revision->fresh()->issued_at);
    }

    public function test_non_route_backed_draft_cannot_issue_even_with_an_explicit_confirmation(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $customer = Customer::query()->create([
            'name' => 'Legacy customer',
            'email' => 'legacy@example.test',
        ]);
        $job = Job::query()->create([
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);
        $revision = JobRevision::query()->create([
            'job_id' => $job->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
        ]);
        $job->update(['current_working_revision_id' => $revision->id]);

        foreach ([
            ['depot_to_pickup', 'EX16 0AA', 'EX1 1AA', 'unloaded', 10],
            ['pickup_to_dropoff', 'EX1 1AA', 'TA1 1AA', 'loaded', 90],
            ['dropoff_to_depot', 'TA1 1AA', 'EX16 0AA', 'unloaded', 96],
        ] as $index => [$label, $startPostcode, $endPostcode, $rateType, $miles]) {
            RouteLeg::query()->create([
                'job_revision_id' => $revision->id,
                'sequence' => $index + 1,
                'label' => $label,
                'start_postcode' => $startPostcode,
                'end_postcode' => $endPostcode,
                'miles' => $miles,
                'manual_miles' => $miles,
                'rate_type' => $rateType,
            ]);
        }

        $revisionPath = "/jobs/{$job->id}/revisions/{$revision->id}";

        $this->from($revisionPath)
            ->post("{$revisionPath}/issue", $this->issueConfirmation($revision))
            ->assertRedirect($revisionPath)
            ->assertSessionHasErrors('issue_checklist');

        $this->assertNull($revision->fresh()->issued_at);
        $this->assertSame('draft', $job->fresh()->status);
    }

    public function test_route_first_draft_rejects_an_ineligible_route_resolution_before_issue(): void
    {
        $this->actingAs(User::factory()->create());
        $revision = $this->createRouteFirstDraft();
        $revision->routeResolution()->update(['pricing_eligible' => false]);
        $revisionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}";

        $this->from($revisionPath)
            ->post("{$revisionPath}/issue", $this->issueConfirmation($revision))
            ->assertRedirect($revisionPath)
            ->assertSessionHasErrors('issue_checklist');

        $this->assertNull($revision->fresh()->issued_at);
    }

    public function test_route_first_draft_rejects_a_route_that_does_not_preserve_all_three_named_legs(): void
    {
        $this->actingAs(User::factory()->create());
        $revision = $this->createRouteFirstDraft();
        $revision->routeResolution->legs()->where('sequence', 2)->update(['leg_type' => 'depot_to_pickup']);
        $revisionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}";

        $this->from($revisionPath)
            ->post("{$revisionPath}/issue", $this->issueConfirmation($revision))
            ->assertRedirect($revisionPath)
            ->assertSessionHasErrors('issue_checklist');

        $this->assertNull($revision->fresh()->issued_at);
    }

    public function test_explicit_inactive_fuel_correction_can_be_issued_without_activation(): void
    {
        $this->actingAs(User::factory()->create());
        $revision = $this->createRouteFirstDraft();
        $activeFuelPrice = WeeklyFuelPrice::query()->where('is_active', true)->firstOrFail();
        $correctedFuelPrice = WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-24',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.6000',
            'is_active' => false,
        ]);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/fuel-price", [
            'weekly_fuel_price_id' => $correctedFuelPrice->id,
        ])->assertRedirect();

        $correctedRevision = $revision->job->fresh()->currentWorkingRevision;
        $revisionPath = "/jobs/{$correctedRevision->job_id}/revisions/{$correctedRevision->id}";

        $issueResponse = $this->from($revisionPath)
            ->post("{$revisionPath}/issue", $this->issueConfirmation($correctedRevision));

        $issueResponse
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertRedirect("{$revisionPath}/issued");

        $this->assertNotNull($correctedRevision->fresh()->issued_at);
        $this->assertTrue($activeFuelPrice->fresh()->is_active);
        $this->assertFalse($correctedFuelPrice->fresh()->is_active);
    }

    public function test_inactive_fuel_without_explicit_correction_cannot_be_issued(): void
    {
        $this->actingAs(User::factory()->create());
        $revision = $this->createRouteFirstDraft();
        $revision->weeklyFuelPrice()->update(['is_active' => false]);
        $revisionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}";

        $this->from($revisionPath)
            ->post("{$revisionPath}/issue", $this->issueConfirmation($revision))
            ->assertRedirect($revisionPath)
            ->assertSessionHasErrors('issue_checklist');

        $this->assertNull($revision->fresh()->issued_at);
    }

    public function test_issue_checklist_sorts_a_loaded_revision_leg_relation_by_sequence_before_validating_labels(): void
    {
        $this->actingAs(User::factory()->create());
        $revision = $this->createRouteFirstDraft();
        $revision->setRelation(
            'routeLegs',
            $revision->routeLegs()->orderByDesc('sequence')->get(),
        );

        $this->assertTrue(app(IssuedQuoteChecklist::class)->isComplete($revision));
    }

    public function test_issued_quote_output_is_private_printable_and_preserves_revision_evidence(): void
    {
        $operator = User::factory()->create(['name' => 'Harriet Vale']);
        $this->actingAs($operator);
        $revision = $this->createRouteFirstDraft();
        $revisionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}";
        $issuedPath = "{$revisionPath}/issued";

        $this->post("{$revisionPath}/issue", $this->issueConfirmation($revision))
            ->assertRedirect($issuedPath);

        $revision->refresh();
        $revision->load(['job.customer', 'routeLegs', 'routeResolution']);

        $this->assertNotNull($revision->issued_at);
        $this->assertSame($revision->issued_at?->format('Y-m-d H:i:s'), $revision->job->issued_at?->format('Y-m-d H:i:s'));
        $this->assertSame('SWEQ-'.$revision->job_id.'-R'.$revision->revision_number, $revision->issue_reference);
        $this->assertSame('Amber Vale Eventing', data_get($revision->issued_evidence, 'customer.name'));
        $this->assertSame('here', data_get($revision->issued_evidence, 'route.provider'));
        $this->assertSame(90, data_get($revision->issued_evidence, 'route.legs.1.miles'));

        $revision->job->customer()->update(['name' => 'Changed after issue']);
        $revision->routeLegs()->where('sequence', 2)->update(['miles' => 999]);
        $revision->routeResolution()->update(['provider' => 'changed_provider']);

        $this->get($issuedPath)
            ->assertOk()
            ->assertSeeText('Issued quote')
            ->assertSeeText('SWEQ-'.$revision->job_id.'-R'.$revision->revision_number)
            ->assertSeeText('Amber Vale Eventing')
            ->assertSeeText('HERE')
            ->assertSeeText('Pickup to drop-off')
            ->assertSeeText('90 miles')
            ->assertSeeText('Engine total')
            ->assertSeeText('Final total')
            ->assertSeeText('Print or save as PDF')
            ->assertDontSeeText('Changed after issue')
            ->assertDontSeeText('changed_provider')
            ->assertDontSeeText('999 miles');

        $this->post('/logout')->assertRedirect('/login');

        $this->get($issuedPath)->assertRedirect('/login');
    }

    public function test_issued_evidence_retains_sanitised_route_review_and_location_evidence(): void
    {
        $operator = User::factory()->create([
            'name' => 'Harriet Vale',
            'can_manage_quote_exceptions' => true,
        ]);
        $this->actingAs($operator);
        $revision = $this->createRouteFirstDraft();
        $resolution = $revision->routeResolution;
        $resolution->update([
            'overall_status' => 'operator_review_required',
            'operator_action_required' => true,
            'pricing_eligible' => true,
            'provider_metadata' => [
                'routing_version' => 'v8',
                'http_status' => 200,
                'api_key' => 'test-secret-key',
            ],
            'normalisation_metadata' => [
                'locations' => [[
                    'latitude' => 50.721,
                    'longitude' => -3.534,
                    'postcode' => 'EX1 1AB',
                ]],
            ],
            'warnings' => [[
                'code' => 'postcode_normalised',
                'message' => 'Pickup postcode was normalised.',
                'context' => [
                    'field' => 'pickup_postcode',
                    'input' => 'EX1 1AA',
                    'canonical_postcode' => 'EX1 1AB',
                ],
            ]],
        ]);
        $resolution->legs()->where('sequence', 2)->update([
            'resolved_origin_metadata' => ['postcode' => 'EX1 1AB'],
            'resolved_destination_metadata' => ['postcode' => 'TA1 1AB'],
        ]);
        RouteResolutionReviewDecision::query()->create([
            'route_resolution_id' => $resolution->id,
            'actor_id' => $operator->id,
            'decision' => 'accepted_for_pricing',
            'recorded_at' => now(),
        ]);
        $revisionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}";

        $this->post("{$revisionPath}/issue", $this->issueConfirmation($revision))
            ->assertRedirect("{$revisionPath}/issued");

        $revision->refresh();

        $this->assertSame('postcode_normalised', data_get($revision->issued_evidence, 'route.warnings.0.code'));
        $this->assertSame('v8', data_get($revision->issued_evidence, 'route.provider_metadata.routing_version'));
        $this->assertSame(200, data_get($revision->issued_evidence, 'route.provider_metadata.http_status'));
        $this->assertNull(data_get($revision->issued_evidence, 'route.provider_metadata.api_key'));
        $this->assertSame('EX1 1AB', data_get($revision->issued_evidence, 'route.normalisation.locations.0.postcode'));
        $this->assertSame('EX1 1AB', data_get($revision->issued_evidence, 'route.legs.1.resolved_origin.postcode'));
        $this->assertSame('TA1 1AB', data_get($revision->issued_evidence, 'route.legs.1.resolved_destination.postcode'));
        $this->assertSame('accepted_for_pricing', data_get($revision->issued_evidence, 'route.review_decisions.0.decision'));
        $this->assertSame('Harriet Vale', data_get($revision->issued_evidence, 'route.review_decisions.0.operator'));
        $this->assertNotNull(data_get($revision->issued_evidence, 'route.review_decisions.0.recorded_at'));

        $this->get("{$revisionPath}/issued")
            ->assertOk()
            ->assertSeeText('Pickup postcode was normalised.')
            ->assertSeeText('EX1 1AB')
            ->assertSeeText('Accepted for pricing')
            ->assertSeeText('Harriet Vale')
            ->assertDontSeeText('test-secret-key');
    }

    private function createRouteFirstDraft(): JobRevision
    {
        $this->createActivePricingContext();
        $enquiry = TransportEnquiry::query()->create([
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
            'status' => 'draft',
        ]);
        $resolution = RouteResolution::query()->create([
            'transport_enquiry_id' => $enquiry->id,
            'provider' => 'here',
            'provider_product' => 'geocoding_and_routing_v8',
            'request_id' => 'route-123',
            'route_profile' => 'car',
            'overall_status' => 'resolved',
            'operator_action_required' => false,
            'pricing_eligible' => true,
            'raw_input_snapshot' => [
                'depot_postcode' => 'EX16 0AA',
                'pickup_postcode' => 'EX1 1AA',
                'dropoff_postcode' => 'TA1 1AA',
            ],
            'request_context' => ['transport_enquiry_id' => $enquiry->id],
            'normalisation_metadata' => [],
            'provider_metadata' => ['routing_version' => 'v8'],
            'warnings' => [],
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

        $this->post("/transport-enquiries/{$enquiry->id}/route-resolutions/{$resolution->id}/accept")
            ->assertRedirect();

        return JobRevision::query()->firstOrFail();
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

    private function issueConfirmation(JobRevision $revision): array
    {
        return [
            'issue_confirmation' => '1',
            'confirmed_revision_id' => $revision->id,
        ];
    }
}
