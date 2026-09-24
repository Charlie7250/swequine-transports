<?php

namespace Tests\Feature\Quotes;

use App\Models\Customer;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\RateSetting;
use App\Models\RouteLeg;
use App\Models\RouteResolution;
use App\Models\RouteResolutionLeg;
use App\Models\TransportEnquiry;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use App\Services\Pricing\JobRevisionPricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuoteWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_update_a_quote_workspace_and_reprice_it_with_a_manual_final_total_override_on_a_new_revision(): void
    {
        $actor = User::factory()->create(['can_manage_quote_exceptions' => true]);
        $this->actingAs($actor);

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);

        $revision = $this->createRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        app(JobRevisionPricingEngine::class)->price($revision);

        $response = $this->patch("/jobs/{$revision->job_id}/revisions/{$revision->id}", [
            'customer_name' => 'Amber Vale Eventing',
            'customer_contact_name' => 'Harriet Vale',
            'customer_email' => 'harriet@example.test',
            'customer_phone' => '07700 900123',
            'customer_postcode' => 'EX2 4AB',
            'customer_notes' => 'Call before arrival',
            'horse_count' => 2,
            'pickup_postcode' => 'EX5 2AA',
            'dropoff_postcode' => 'TA2 8QQ',
            'revision_notes' => 'Needs evening drop-off',
            'manual_final_total' => '240.00',
            'manual_final_total_reason_category' => 'customer_agreement',
            'manual_final_total_reason' => 'Evening surcharge agreed',
            'route_legs' => [
                ['miles' => 12],
                ['miles' => 85],
                ['miles' => 100],
            ],
        ]);

        $updatedRevision = JobRevision::query()
            ->where('job_id', $revision->job_id)
            ->orderByDesc('revision_number')
            ->firstOrFail();
        $revision->refresh();
        $updatedRevision->load('job.customer');
        $legs = $updatedRevision->routeLegs()->orderBy('sequence')->get()->values();

        $response->assertRedirect("/jobs/{$revision->job_id}/revisions/{$updatedRevision->id}");
        $this->assertSame(2, JobRevision::query()->where('job_id', $revision->job_id)->count());
        $this->assertSame($updatedRevision->id, $updatedRevision->job->current_working_revision_id);
        $this->assertSame(1, $revision->revision_number);
        $this->assertSame(2, $updatedRevision->revision_number);
        $this->assertSame(1, $revision->horse_count);
        $this->assertSame('EX1 1AA', $revision->pickup_postcode);
        $this->assertSame('TA1 1AA', $revision->dropoff_postcode);
        $this->assertSame('Amber Vale Eventing', $updatedRevision->job->customer->name);
        $this->assertSame('Harriet Vale', $updatedRevision->job->customer->contact_name);
        $this->assertSame('harriet@example.test', $updatedRevision->job->customer->email);
        $this->assertSame('07700 900123', $updatedRevision->job->customer->phone);
        $this->assertSame('EX2 4AB', $updatedRevision->job->customer->postcode);
        $this->assertSame('Call before arrival', $updatedRevision->job->customer->notes);
        $this->assertSame(2, $updatedRevision->horse_count);
        $this->assertSame('EX5 2AA', $updatedRevision->pickup_postcode);
        $this->assertSame('TA2 8QQ', $updatedRevision->dropoff_postcode);
        $this->assertSame('Needs evening drop-off', $updatedRevision->notes);
        $this->assertSame('217.77', $updatedRevision->engine_total);
        $this->assertSame('240.00', $updatedRevision->final_total);
        $this->assertSame('manual_final_total', $updatedRevision->calculation_explanation['overrides'][0]['type']);
        $this->assertSame('240.00', $updatedRevision->calculation_explanation['overrides'][0]['amount']);
        $audit = $updatedRevision->quoteExceptionAudits()->where('exception_type', 'final_total_override')->firstOrFail();
        $this->assertSame('customer_agreement', $audit->reason_category);
        $this->assertSame('Evening surcharge agreed', $audit->explanation);
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertSame('217.77', $audit->original_value);
        $this->assertSame('240.00', $audit->replacement_value);
        $this->assertSame(12, $legs[0]->manual_miles);
        $this->assertSame(85, $legs[1]->manual_miles);
        $this->assertSame(100, $legs[2]->manual_miles);
    }

    public function test_staff_without_exception_permission_cannot_use_the_workspace_final_total_override(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createWeeklyFuelPrice(['is_active' => true, 'activated_at' => now()]);
        $this->createRateSetting(['is_active' => true]);
        $revision = $this->createRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->patch(
            "/jobs/{$revision->job_id}/revisions/{$revision->id}",
            $this->workspacePayload([
                'manual_final_total' => '240.00',
                'manual_final_total_reason_category' => 'customer_agreement',
                'manual_final_total_reason' => 'Evening surcharge agreed',
            ]),
        )->assertForbidden();

        $this->assertSame(1, JobRevision::query()->where('job_id', $revision->job_id)->count());
    }

    public function test_workspace_final_total_override_requires_exactly_two_decimal_places(): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createWeeklyFuelPrice(['is_active' => true, 'activated_at' => now()]);
        $this->createRateSetting(['is_active' => true]);
        $revision = $this->createRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->from("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->patch(
                "/jobs/{$revision->job_id}/revisions/{$revision->id}",
                $this->workspacePayload([
                    'manual_final_total' => '240.0',
                    'manual_final_total_reason_category' => 'customer_agreement',
                    'manual_final_total_reason' => 'Evening surcharge agreed',
                ]),
            )
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->assertSessionHasErrors('manual_final_total');

        $this->assertSame(1, JobRevision::query()->where('job_id', $revision->job_id)->count());
    }

    #[DataProvider('invalidWorkspaceOverrideEvidence')]
    public function test_workspace_override_rejects_invalid_evidence(array $override, string $errorField): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createWeeklyFuelPrice(['is_active' => true, 'activated_at' => now()]);
        $this->createRateSetting(['is_active' => true]);
        $revision = $this->createRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->from("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->patch(
                "/jobs/{$revision->job_id}/revisions/{$revision->id}",
                $this->workspacePayload($override),
            )
            ->assertSessionHasErrors($errorField);

        $this->assertSame(1, JobRevision::query()->where('job_id', $revision->job_id)->count());
    }

    public function test_workspace_pricing_rejects_horse_counts_above_two_for_manual_review(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createWeeklyFuelPrice(['is_active' => true, 'activated_at' => now()]);
        $this->createRateSetting(['is_active' => true]);
        $revision = $this->createRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->from("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->patch(
                "/jobs/{$revision->job_id}/revisions/{$revision->id}",
                $this->workspacePayload(['horse_count' => 3]),
            )
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->assertSessionHasErrors('horse_count');

        $this->assertSame(1, JobRevision::query()->where('job_id', $revision->job_id)->count());
    }

    public function test_staff_can_issue_a_quote_then_mark_it_pending_and_return_it_to_draft(): void
    {
        $this->actingAs(User::factory()->create());

        $fuelPrice = $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $rateSetting = $this->createRateSetting([
            'is_active' => true,
        ]);
        $revision = $this->createRevision();

        $this->createStandardRouteLegs($revision, [10, 90, 96]);
        $this->attachAcceptedEligibleRoute($revision);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/issue", $this->issueConfirmation($revision))
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}/issued");

        $revision->refresh();
        $job = $revision->job()->firstOrFail();

        $this->assertSame($fuelPrice->id, $revision->weekly_fuel_price_id);
        $this->assertSame($rateSetting->id, $revision->rate_setting_id);
        $this->assertSame('255.90', $revision->engine_total);
        $this->assertSame('quoted', $job->status);
        $this->assertSame($revision->id, $job->current_working_revision_id);
        $this->assertSame($revision->id, $job->issued_revision_id);
        $this->assertNotNull($job->issued_at);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/pending")
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}");

        $job->refresh();

        $this->assertSame('pending', $job->status);
        $this->assertSame($revision->id, $job->issued_revision_id);
        $this->assertNotNull($job->issued_at);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/draft")
            ->assertRedirect();

        $job->refresh();
        $draftRevision = JobRevision::query()->orderByDesc('revision_number')->firstOrFail();

        $this->assertSame('draft', $job->status);
        $this->assertSame($draftRevision->id, $job->current_working_revision_id);
        $this->assertSame($revision->id, $job->issued_revision_id);
        $this->assertNotNull($job->issued_at);
        $this->assertSame($revision->engine_total, $draftRevision->engine_total);
    }

    public function test_staff_can_book_a_quote_and_mark_it_completed_with_reporting_dates(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);
        $revision = $this->createRevision();

        $this->createStandardRouteLegs($revision, [10, 90, 96]);
        $this->attachAcceptedEligibleRoute($revision);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/issue", $this->issueConfirmation($revision))
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}/issued");
        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/pending")
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}");
        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/book")
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}");

        $job = $revision->job()->firstOrFail();

        $this->assertSame('booked', $job->status);
        $this->assertSame($revision->id, $job->accepted_revision_id);
        $this->assertNotNull($job->issued_at);
        $this->assertNotNull($job->booked_at);
        $this->assertNull($job->completed_at);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/complete")
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}");

        $job->refresh();

        $this->assertSame('completed', $job->status);
        $this->assertSame($revision->id, $job->accepted_revision_id);
        $this->assertNotNull($job->issued_at);
        $this->assertNotNull($job->booked_at);
        $this->assertNotNull($job->completed_at);
    }

    public function test_booking_an_issued_quote_preserves_its_totals_route_and_pricing_explanation(): void
    {
        $this->actingAs(User::factory()->create());

        $fuelPrice = $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $rateSetting = $this->createRateSetting([
            'is_active' => true,
        ]);
        $revision = $this->createRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);
        $this->attachAcceptedEligibleRoute($revision);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/issue", $this->issueConfirmation($revision))
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}/issued");

        $issuedRevision = $revision->fresh();
        $issuedRouteEvidence = $this->routeEvidence($issuedRevision);
        $issuedEngineTotal = $issuedRevision->engine_total;
        $issuedFinalTotal = $issuedRevision->final_total;
        $issuedExplanation = $issuedRevision->calculation_explanation;

        $fuelPrice->update(['price_per_litre_inc_vat' => '2.4000']);
        $rateSetting->update(['loaded_add_on_per_mile' => '1.400000']);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/pending")->assertRedirect();
        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/book")->assertRedirect();

        $bookedRevision = $revision->fresh();

        $this->assertSame($issuedEngineTotal, $bookedRevision->engine_total);
        $this->assertSame($issuedFinalTotal, $bookedRevision->final_total);
        $this->assertSame($issuedExplanation, $bookedRevision->calculation_explanation);
        $this->assertSame($issuedRouteEvidence, $this->routeEvidence($bookedRevision));
    }

    public function test_quote_creation_screen_starts_the_route_first_transport_enquiry(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/quotes/create');

        $response->assertOk();
        $response->assertSeeText('New transport enquiry');
        $response->assertSeeText('Loading practice is separate from transport quoting.');
        $response->assertSeeText('Resolve route and miles');
        $response->assertDontSee('manual_final_total');
        $response->assertDontSee('route_legs');
    }

    public function test_quote_update_rolls_back_when_pricing_cannot_resolve_active_records_for_an_unpriced_revision(): void
    {
        $this->actingAs(User::factory()->create());

        $revision = $this->createRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);
        $originalCustomerName = $revision->job->customer->name;

        $this->from("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->patch("/jobs/{$revision->job_id}/revisions/{$revision->id}", [
                'customer_name' => 'Amber Vale Eventing',
                'customer_contact_name' => 'Harriet Vale',
                'customer_email' => 'harriet@example.test',
                'customer_phone' => '07700 900123',
                'customer_postcode' => 'EX2 4AB',
                'customer_notes' => 'Call before arrival',
                'horse_count' => 2,
                'pickup_postcode' => 'EX5 2AA',
                'dropoff_postcode' => 'TA2 8QQ',
                'revision_notes' => 'Needs evening drop-off',
                'manual_final_total' => '',
                'manual_final_total_reason' => '',
                'route_legs' => [
                    ['miles' => 12],
                    ['miles' => 85],
                    ['miles' => 100],
                ],
            ])
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->assertSessionHasErrors('pricing');

        $revision->refresh();
        $revision->load('job.customer');
        $legs = $revision->routeLegs()->orderBy('sequence')->get()->values();

        $this->assertSame($originalCustomerName, $revision->job->customer->name);
        $this->assertSame(1, $revision->horse_count);
        $this->assertSame('EX1 1AA', $revision->pickup_postcode);
        $this->assertSame('TA1 1AA', $revision->dropoff_postcode);
        $this->assertSame(10, $legs[0]->manual_miles);
        $this->assertSame(90, $legs[1]->manual_miles);
        $this->assertSame(96, $legs[2]->manual_miles);
    }

    public function test_status_actions_reject_invalid_quote_transitions(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);
        $revision = $this->createRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);
        $this->attachAcceptedEligibleRoute($revision);

        $this->from("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/pending")
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->assertSessionHasErrors('status');

        $job = $revision->job()->firstOrFail();

        $this->assertSame('draft', $job->status);
        $this->assertNull($job->issued_at);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/issue", $this->issueConfirmation($revision))
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}/issued");

        $job->refresh();
        $issuedAt = $job->issued_at;

        $this->from("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/issue", $this->issueConfirmation($revision))
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->assertSessionHasErrors('status');

        $job->refresh();

        $this->assertSame('quoted', $job->status);
        $this->assertTrue($job->issued_at?->equalTo($issuedAt));
    }

    public function test_editing_a_pre_release_issued_quote_creates_a_new_draft_revision_without_changing_the_issued_revision(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);
        $revision = $this->createRevision();

        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->markPreReleaseRevisionAsIssued($revision);

        $this->from("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->patch("/jobs/{$revision->job_id}/revisions/{$revision->id}", [
                'customer_name' => 'Amber Vale Eventing',
                'customer_contact_name' => 'Harriet Vale',
                'customer_email' => 'harriet@example.test',
                'customer_phone' => '07700 900123',
                'customer_postcode' => 'EX2 4AB',
                'customer_notes' => 'Call before arrival',
                'horse_count' => 2,
                'pickup_postcode' => 'EX5 2AA',
                'dropoff_postcode' => 'TA2 8QQ',
                'revision_notes' => 'Needs evening drop-off',
                'manual_final_total' => '',
                'manual_final_total_reason' => '',
                'route_legs' => [
                    ['miles' => 12],
                    ['miles' => 85],
                    ['miles' => 100],
                ],
            ])
            ->assertRedirect();

        $revision->refresh();
        $revision->load('job.customer');
        $legs = $revision->routeLegs()->orderBy('sequence')->get()->values();
        $draftRevision = JobRevision::query()->orderByDesc('revision_number')->firstOrFail();

        $this->assertSame('draft', $revision->job->status);
        $this->assertSame($revision->id, $revision->job->issued_revision_id);
        $this->assertSame($draftRevision->id, $revision->job->current_working_revision_id);
        $this->assertSame('Amber Vale Eventing', $revision->job->customer->name);
        $this->assertSame(1, $revision->horse_count);
        $this->assertSame('EX1 1AA', $revision->pickup_postcode);
        $this->assertSame('TA1 1AA', $revision->dropoff_postcode);
        $this->assertSame(10, $legs[0]->manual_miles);
        $this->assertSame(90, $legs[1]->manual_miles);
        $this->assertSame(96, $legs[2]->manual_miles);
        $this->assertSame(2, $draftRevision->horse_count);
        $this->assertSame('EX5 2AA', $draftRevision->pickup_postcode);
        $this->assertSame('TA2 8QQ', $draftRevision->dropoff_postcode);
    }

    public function test_editing_a_pre_release_pending_quote_creates_a_new_draft_revision_without_clearing_the_issue_record(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);
        $revision = $this->createRevision();

        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->markPreReleaseRevisionAsIssued($revision);
        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/pending")
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}");

        $this->from("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->patch("/jobs/{$revision->job_id}/revisions/{$revision->id}", [
                'customer_name' => 'Amber Vale Eventing',
                'customer_contact_name' => 'Harriet Vale',
                'customer_email' => 'harriet@example.test',
                'customer_phone' => '07700 900123',
                'customer_postcode' => 'EX2 4AB',
                'customer_notes' => 'Call before arrival',
                'horse_count' => 2,
                'pickup_postcode' => 'EX5 2AA',
                'dropoff_postcode' => 'TA2 8QQ',
                'revision_notes' => 'Needs evening drop-off',
                'manual_final_total' => '',
                'manual_final_total_reason' => '',
                'route_legs' => [
                    ['miles' => 12],
                    ['miles' => 85],
                    ['miles' => 100],
                ],
            ])
            ->assertRedirect();

        $revision->refresh();
        $revision->load('job.customer');
        $legs = $revision->routeLegs()->orderBy('sequence')->get()->values();
        $draftRevision = JobRevision::query()->orderByDesc('revision_number')->firstOrFail();

        $this->assertSame('draft', $revision->job->status);
        $this->assertSame($revision->id, $revision->job->issued_revision_id);
        $this->assertNotNull($revision->job->issued_at);
        $this->assertSame($draftRevision->id, $revision->job->current_working_revision_id);
        $this->assertSame('Amber Vale Eventing', $revision->job->customer->name);
        $this->assertSame(1, $revision->horse_count);
        $this->assertSame('EX1 1AA', $revision->pickup_postcode);
        $this->assertSame('TA1 1AA', $revision->dropoff_postcode);
        $this->assertSame(10, $legs[0]->manual_miles);
        $this->assertSame(90, $legs[1]->manual_miles);
        $this->assertSame(96, $legs[2]->manual_miles);
        $this->assertSame(2, $draftRevision->horse_count);
    }

    public function test_corrected_fuel_selection_creates_a_new_unoverridden_draft_revision(): void
    {
        $actor = User::factory()->create(['can_manage_quote_exceptions' => true]);
        $this->actingAs($actor);
        $activeFuelPrice = $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $correctedFuelPrice = $this->createWeeklyFuelPrice([
            'week_commencing' => '2026-08-17',
            'price_per_litre_inc_vat' => '1.6000',
        ]);
        $this->createRateSetting(['is_active' => true]);
        $revision = $this->createRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);
        app(JobRevisionPricingEngine::class)->price($revision);
        $issuedEvidence = ['revision_id' => $revision->id, 'engine_total' => $revision->engine_total];
        $revision->forceFill([
            'issued_at' => now()->subHour(),
            'issued_by_user_id' => $actor->id,
            'issue_reference' => 'SWEQ-TEST-ISSUED',
            'issued_evidence' => $issuedEvidence,
        ])->save();
        $revision->job()->update([
            'issued_revision_id' => $revision->id,
            'issued_at' => $revision->issued_at,
        ]);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/exceptions/final-total", [
            'final_total' => '250.00',
            'reason_category' => 'commercial_adjustment',
            'explanation' => 'Approved rounded customer total',
        ])->assertRedirect();

        $overriddenRevision = $revision->job->fresh()->currentWorkingRevision;
        $this->post(
            "/jobs/{$revision->job_id}/revisions/{$overriddenRevision->id}/fuel-price",
            ['weekly_fuel_price_id' => $correctedFuelPrice->id],
        )->assertRedirect();

        $revision->job->refresh();
        $correctedRevision = $revision->job->currentWorkingRevision;

        $this->assertSame('250.00', $overriddenRevision->fresh()->final_total);
        $this->assertSame('255.90', $overriddenRevision->fresh()->engine_total);
        $this->assertSame($correctedFuelPrice->id, $correctedRevision->weekly_fuel_price_id);
        $this->assertSame('259.39', $correctedRevision->engine_total);
        $this->assertSame('259.39', $correctedRevision->final_total);
        $this->assertNull($correctedRevision->manual_final_total_reason);
        $this->assertSame([], $correctedRevision->calculation_explanation['overrides']);
        $this->assertSame('explicit_correction', data_get($correctedRevision->calculation_explanation, 'fuel_context.selection_type'));
        $this->assertSame($correctedFuelPrice->id, data_get($correctedRevision->calculation_explanation, 'fuel_context.selected_weekly_fuel_price_id'));
        $this->assertFalse($correctedRevision->quoteExceptionAudits()->where('exception_type', 'final_total_override')->exists());
        $this->assertTrue($activeFuelPrice->fresh()->is_active);
        $this->assertFalse($correctedFuelPrice->fresh()->is_active);
        $this->assertSame($revision->id, $revision->job->fresh()->issued_revision_id);
        $this->assertSame($issuedEvidence, $revision->fresh()->issued_evidence);
    }

    private function createWeeklyFuelPrice(array $overrides = []): WeeklyFuelPrice
    {
        return WeeklyFuelPrice::query()->create(array_merge([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => false,
            'activated_at' => null,
        ], $overrides));
    }

    public static function invalidWorkspaceOverrideEvidence(): array
    {
        return [
            'unapproved category' => [[
                'manual_final_total' => '240.00',
                'manual_final_total_reason_category' => 'unapproved_category',
                'manual_final_total_reason' => 'Evening surcharge agreed',
            ], 'manual_final_total_reason_category'],
            'missing explanation' => [[
                'manual_final_total' => '240.00',
                'manual_final_total_reason_category' => 'customer_agreement',
                'manual_final_total_reason' => '',
            ], 'manual_final_total_reason'],
            'non-positive amount' => [[
                'manual_final_total' => '0.00',
                'manual_final_total_reason_category' => 'customer_agreement',
                'manual_final_total_reason' => 'Evening surcharge agreed',
            ], 'manual_final_total'],
        ];
    }

    private function workspacePayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Amber Vale Eventing',
            'horse_count' => 1,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
            'manual_final_total' => '',
            'manual_final_total_reason_category' => '',
            'manual_final_total_reason' => '',
            'route_legs' => [
                ['miles' => 10],
                ['miles' => 90],
                ['miles' => 96],
            ],
        ], $overrides);
    }

    private function createRateSetting(array $overrides = []): RateSetting
    {
        return RateSetting::query()->create(array_merge([
            'name' => 'Initial internal rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.150000',
            'is_active' => false,
            'effective_from' => '2026-08-10',
        ], $overrides));
    }

    private function createRevision(array $customerOverrides = [], array $jobOverrides = [], array $revisionOverrides = []): JobRevision
    {
        $customer = Customer::query()->create(array_merge([
            'name' => 'South West Equine Customer',
            'contact_name' => 'Existing Contact',
            'email' => 'existing@example.test',
            'phone' => '07700 900000',
            'postcode' => 'EX1 9ZZ',
            'notes' => 'Existing notes',
        ], $customerOverrides));

        $job = Job::query()->create(array_merge([
            'customer_id' => $customer->id,
            'status' => 'draft',
        ], $jobOverrides));

        $revision = JobRevision::query()->create(array_merge([
            'job_id' => $job->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
        ], $revisionOverrides));

        Job::query()->whereKey($revision->job_id)->update([
            'current_working_revision_id' => $revision->id,
        ]);

        return $revision;
    }

    private function createStandardRouteLegs(JobRevision $revision, array $miles): void
    {
        $definitions = [
            [
                'sequence' => 1,
                'label' => 'depot_to_pickup',
                'rate_type' => 'unloaded',
                'start_postcode' => 'EX16 0AA',
                'end_postcode' => $revision->pickup_postcode,
            ],
            [
                'sequence' => 2,
                'label' => 'pickup_to_dropoff',
                'rate_type' => 'loaded',
                'start_postcode' => $revision->pickup_postcode,
                'end_postcode' => $revision->dropoff_postcode,
            ],
            [
                'sequence' => 3,
                'label' => 'dropoff_to_depot',
                'rate_type' => 'unloaded',
                'start_postcode' => $revision->dropoff_postcode,
                'end_postcode' => 'EX16 0AA',
            ],
        ];

        foreach ($definitions as $index => $definition) {
            RouteLeg::query()->create([
                'job_revision_id' => $revision->id,
                'sequence' => $definition['sequence'],
                'label' => $definition['label'],
                'start_postcode' => $definition['start_postcode'],
                'end_postcode' => $definition['end_postcode'],
                'miles' => $miles[$index],
                'manual_miles' => $miles[$index],
                'rate_type' => $definition['rate_type'],
            ]);
        }
    }

    private function routeEvidence(JobRevision $revision): array
    {
        return $revision->routeLegs()
            ->orderBy('sequence')
            ->get()
            ->map(fn (RouteLeg $routeLeg): array => [
                'miles' => $routeLeg->miles,
                'manual_miles' => $routeLeg->manual_miles,
                'rate_per_mile' => $routeLeg->rate_per_mile,
                'amount' => $routeLeg->amount,
            ])
            ->all();
    }

    private function issueConfirmation(JobRevision $revision): array
    {
        return [
            'issue_confirmation' => '1',
            'confirmed_revision_id' => $revision->id,
        ];
    }

    private function attachAcceptedEligibleRoute(JobRevision $revision): void
    {
        $enquiry = TransportEnquiry::query()->create([
            'source' => 'direct_contact',
            'customer_name' => $revision->job->customer->name,
            'email' => $revision->job->customer->email,
            'phone' => $revision->job->customer->phone,
            'pickup_postcode' => $revision->pickup_postcode,
            'dropoff_postcode' => $revision->dropoff_postcode,
            'horse_count' => $revision->horse_count,
            'requested_date' => '2026-09-01',
            'special_constraints_acknowledged' => true,
            'status' => 'draft_quote',
            'quote_job_id' => $revision->job_id,
        ]);
        $resolution = RouteResolution::query()->create([
            'transport_enquiry_id' => $enquiry->id,
            'provider' => 'here',
            'provider_product' => 'geocoding_and_routing_v8',
            'route_profile' => 'car',
            'overall_status' => 'resolved',
            'pricing_eligible' => true,
            'provider_metadata' => [],
            'normalisation_metadata' => [],
            'warnings' => [],
            'resolved_at' => now(),
            'attempted_at' => now(),
        ]);

        foreach ($revision->routeLegs()->orderBy('sequence')->get() as $routeLeg) {
            RouteResolutionLeg::query()->create([
                'route_resolution_id' => $resolution->id,
                'sequence' => $routeLeg->sequence,
                'leg_type' => match ($routeLeg->label) {
                    'depot_to_pickup' => 'depot_to_pickup',
                    'pickup_to_dropoff' => 'pickup_to_drop_off',
                    default => 'drop_off_to_depot',
                },
                'origin_input' => $routeLeg->start_postcode,
                'destination_input' => $routeLeg->end_postcode,
                'status' => 'resolved',
                'distance_metres' => $routeLeg->miles * 1609,
                'quoted_miles' => $routeLeg->miles,
                'duration_seconds' => 600,
                'provider_route_id' => 'route-'.$routeLeg->sequence,
                'warnings' => [],
            ]);
        }

        $revision->update(['route_resolution_id' => $resolution->id]);
        $revision->routeLegs()->update(['manual_miles' => null]);
    }

    private function markPreReleaseRevisionAsIssued(JobRevision $revision): void
    {
        $revision->job()->update([
            'status' => 'quoted',
            'current_working_revision_id' => $revision->id,
            'issued_revision_id' => $revision->id,
            'issued_at' => now(),
        ]);
    }
}
