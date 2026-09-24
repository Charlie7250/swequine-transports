<?php

namespace Tests\Feature\Quotes;

use App\Models\JobRevision;
use App\Models\RateSetting;
use App\Models\RouteResolution;
use App\Models\RouteResolutionLeg;
use App\Models\TransportEnquiry;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use App\Services\Intake\TransportEnquiryQuoteWorkflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ControlledRouteExceptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_creates_a_new_route_resolution_without_changing_the_failed_attempt(): void
    {
        $operator = User::factory()->create(['name' => 'Harriet Vale', 'can_manage_quote_exceptions' => true]);
        $this->actingAs($operator);
        $this->createActivePricingContext();
        $this->configureHere();
        Http::fake(fn (): mixed => Http::response(['error' => 'unavailable'], 503));

        $this->post('/transport-enquiries/resolve', array_merge($this->completeEnquiryPayload(), [
            'submission_token' => $this->resolveSubmissionToken(),
        ]))->assertRedirect();

        $failedAttempt = RouteResolution::query()->firstOrFail();
        $failedSnapshot = $failedAttempt->raw_input_snapshot;

        $this->fakeSuccessfulHereRequests();

        $this->post("/transport-enquiries/{$failedAttempt->transport_enquiry_id}/route-resolutions/{$failedAttempt->id}/retry")
            ->assertRedirect();

        $retryAttempt = RouteResolution::query()->orderByDesc('id')->firstOrFail();

        $this->assertSame(2, RouteResolution::query()->count());
        $this->assertSame('provider_unavailable', $failedAttempt->fresh()->overall_status);
        $this->assertSame($failedSnapshot, $failedAttempt->fresh()->raw_input_snapshot);
        $this->assertSame('retry', $retryAttempt->attempt_kind);
        $this->assertSame($failedAttempt->id, $retryAttempt->previous_route_resolution_id);
        $this->assertSame('provider_unavailable', $retryAttempt->overall_status);
    }

    public function test_postcode_correction_creates_a_new_attempt_and_keeps_the_original_input_snapshot(): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createActivePricingContext();
        $failedAttempt = $this->createRouteResolution('operator_review_required', false, [null, null, null]);
        $originalSnapshot = $failedAttempt->raw_input_snapshot;
        $this->configureHere();
        $this->fakeSuccessfulHereRequests();

        $this->post("/transport-enquiries/{$failedAttempt->transport_enquiry_id}/route-resolutions/{$failedAttempt->id}/correct", [
            'pickup_postcode' => 'EX2 4AB',
            'dropoff_postcode' => 'TA2 8QQ',
        ])->assertRedirect();

        $correctedAttempt = RouteResolution::query()->orderByDesc('id')->firstOrFail();

        $this->assertSame(2, RouteResolution::query()->count());
        $this->assertSame($originalSnapshot, $failedAttempt->fresh()->raw_input_snapshot);
        $this->assertSame('correction', $correctedAttempt->attempt_kind);
        $this->assertSame($failedAttempt->id, $correctedAttempt->previous_route_resolution_id);
        $this->assertSame('EX2 4AB', $correctedAttempt->raw_input_snapshot['pickup_postcode']);
        $this->assertSame('TA2 8QQ', $correctedAttempt->raw_input_snapshot['dropoff_postcode']);
    }

    public function test_route_review_hides_exception_controls_for_a_clean_route_and_exposes_them_for_a_warning_and_outage(): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createActivePricingContext();

        $cleanRoute = $this->createRouteResolution();
        $warningRoute = $this->createRouteResolution('resolved_with_warning', true, [10, 90, 96]);
        $outageRoute = $this->createRouteResolution('provider_unavailable', false, [null, null, null]);

        $this->get("/transport-enquiries/{$cleanRoute->transport_enquiry_id}/route-resolutions/{$cleanRoute->id}")
            ->assertOk()
            ->assertDontSee('Handle route exception')
            ->assertDontSee('name="route_legs[0][miles]"', false);

        $this->get("/transport-enquiries/{$warningRoute->transport_enquiry_id}/route-resolutions/{$warningRoute->id}")
            ->assertOk()
            ->assertSeeText('Handle route exception')
            ->assertDontSee('name="route_legs[0][miles]"', false);

        $this->get("/transport-enquiries/{$outageRoute->transport_enquiry_id}/route-resolutions/{$outageRoute->id}")
            ->assertOk()
            ->assertSeeText('Handle route exception')
            ->assertDontSee('name="route_legs[0][miles]"', false);
    }

    public function test_route_review_and_exception_views_show_safe_location_and_failure_evidence(): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createActivePricingContext();
        $reviewRoute = $this->createRouteResolution('operator_review_required', false, [10, 90, 96]);
        $reviewLeg = $reviewRoute->legs()->where('sequence', 2)->firstOrFail();
        $reviewLeg->update([
            'resolved_origin_metadata' => ['postcode' => 'EX1 1AB'],
            'resolved_destination_metadata' => ['postcode' => 'TA1 1AB'],
            'failure_detail' => [
                'code' => 'unresolved',
                'message' => 'Provider trace token must never be shown to operators.',
            ],
        ]);

        $reviewPath = "/transport-enquiries/{$reviewRoute->transport_enquiry_id}/route-resolutions/{$reviewRoute->id}";
        $exceptionPath = "{$reviewPath}/exceptions";

        $this->get($reviewPath)
            ->assertOk()
            ->assertSeeText('Location review')
            ->assertSeeText('Entered origin')
            ->assertSeeText('EX1 1AA')
            ->assertSeeText('EX1 1AB')
            ->assertSeeText('TA1 1AB')
            ->assertSeeText('No reliable route was found for this route leg.')
            ->assertDontSeeText('Provider trace token must never be shown to operators.');

        $this->get($exceptionPath)
            ->assertOk()
            ->assertSeeText('Location review')
            ->assertSeeText('EX1 1AB')
            ->assertSeeText('No reliable route was found for this route leg.')
            ->assertDontSeeText('Provider trace token must never be shown to operators.');
    }

    public function test_untrained_staff_cannot_open_or_apply_route_exceptions(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createActivePricingContext();
        $outageRoute = $this->createRouteResolution('provider_unavailable', false, [null, null, null]);
        $exceptionPath = "/transport-enquiries/{$outageRoute->transport_enquiry_id}/route-resolutions/{$outageRoute->id}/exceptions";

        $this->get($exceptionPath)->assertForbidden();

        $this->post("{$exceptionPath}/manual-miles", [
            'reason_category' => 'provider_outage',
            'explanation' => 'The provider remained unavailable while the customer waited.',
            'route_legs' => [
                ['miles' => 12],
                ['miles' => 88],
                ['miles' => 99],
            ],
        ])->assertForbidden();

        $this->post("/transport-enquiries/{$outageRoute->transport_enquiry_id}/route-resolutions/{$outageRoute->id}/retry")
            ->assertForbidden();

        $this->get("/transport-enquiries/{$outageRoute->transport_enquiry_id}/route-resolutions/{$outageRoute->id}")
            ->assertDontSeeText('Handle route exception');

        $reviewRoute = $this->createRouteResolution('operator_review_required', false, [10, 90, 96]);
        $reviewPath = "/transport-enquiries/{$reviewRoute->transport_enquiry_id}/route-resolutions/{$reviewRoute->id}";

        $this->get($reviewPath)->assertDontSeeText('Accept reviewed route for quote');
        $this->post("{$reviewPath}/accept")->assertForbidden();

        $this->assertDatabaseCount('job_revisions', 0);
    }

    public function test_route_retry_service_requires_an_authorised_operator(): void
    {
        $this->createActivePricingContext();
        $untrainedStaff = User::factory()->create();
        $outageRoute = $this->createRouteResolution('provider_unavailable', false, [null, null, null]);

        $this->expectException(AuthorizationException::class);

        app(TransportEnquiryQuoteWorkflow::class)->retry(
            $outageRoute->transportEnquiry()->firstOrFail(),
            $outageRoute,
            $untrainedStaff->id,
        );
    }

    public function test_provider_outage_manual_fallback_creates_a_complete_draft_and_audits_every_changed_leg(): void
    {
        $operator = User::factory()->create(['name' => 'Harriet Vale', 'can_manage_quote_exceptions' => true]);
        $this->actingAs($operator);
        $this->createActivePricingContext();
        $outageRoute = $this->createRouteResolution('provider_unavailable', false, [null, null, null]);
        $exceptionPath = "/transport-enquiries/{$outageRoute->transport_enquiry_id}/route-resolutions/{$outageRoute->id}/exceptions";

        $this->get($exceptionPath)
            ->assertOk()
            ->assertSeeText('Use manual miles because route lookup is unavailable')
            ->assertSeeText('Original route value');

        $response = $this->post("{$exceptionPath}/manual-miles", [
            'reason_category' => 'provider_outage',
            'explanation' => 'The provider remained unavailable while the customer waited.',
            'route_legs' => [
                ['miles' => 12],
                ['miles' => 88],
                ['miles' => 99],
            ],
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $revision = JobRevision::query()->firstOrFail();

        $response->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}");
        $this->assertSame([12, 88, 99], $revision->routeLegs()->orderBy('sequence')->pluck('manual_miles')->all());
        $this->assertDatabaseCount('quote_exception_audits', 3);
        $this->assertDatabaseHas('quote_exception_audits', [
            'job_revision_id' => $revision->id,
            'exception_type' => 'manual_route_fallback',
            'reason_category' => 'provider_outage',
            'explanation' => 'The provider remained unavailable while the customer waited.',
            'actor_id' => $operator->id,
            'original_value' => null,
            'replacement_value' => '12',
        ]);

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/issue", [
            'issue_confirmation' => '1',
            'confirmed_revision_id' => $revision->id,
        ])->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}/issued");

        $this->assertNotNull($revision->fresh()->issued_at);
        $this->assertNotNull(
            \DB::table('quote_exception_audits')->where('job_revision_id', $revision->id)->value('recorded_at'),
        );

        $this->get("/jobs/{$revision->job_id}/revisions/{$revision->id}")
            ->assertOk()
            ->assertSeeText('Manual route miles')
            ->assertSeeText('Original route value')
            ->assertSeeText('Unavailable')
            ->assertSeeText('12');
    }

    public function test_manual_fallback_for_unsupported_horse_count_rolls_back_all_quote_records(): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createActivePricingContext();
        $outageRoute = $this->createRouteResolution('provider_unavailable', false, [null, null, null]);
        $outageRoute->transportEnquiry()->update(['horse_count' => 3]);
        $exceptionPath = "/transport-enquiries/{$outageRoute->transport_enquiry_id}/route-resolutions/{$outageRoute->id}/exceptions";

        $this->from($exceptionPath)
            ->post("{$exceptionPath}/manual-miles", [
                'reason_category' => 'provider_outage',
                'explanation' => 'The provider remained unavailable while the customer waited.',
                'route_legs' => [
                    ['miles' => 12],
                    ['miles' => 88],
                    ['miles' => 99],
                ],
            ])
            ->assertRedirect($exceptionPath)
            ->assertSessionHasErrors([
                'pricing' => 'Automatic transport pricing supports one or two horses. Counts above two require manual review.',
            ]);

        $enquiry = $outageRoute->transportEnquiry()->firstOrFail();

        $this->assertSame('draft', $enquiry->status);
        $this->assertNull($enquiry->quote_job_id);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('job_revisions', 0);
        $this->assertDatabaseCount('route_legs', 0);
        $this->assertDatabaseCount('shared_run_allocations', 0);
        $this->assertDatabaseCount('quote_exception_audits', 0);
    }

    public function test_review_required_manual_fallback_can_issue_when_its_exception_audit_records_the_acceptance(): void
    {
        $operator = User::factory()->create(['can_manage_quote_exceptions' => true]);
        $this->actingAs($operator);
        $this->createActivePricingContext();
        $reviewRoute = $this->createRouteResolution('operator_review_required', false, [10, 90, 96]);
        $fallbackPath = "/transport-enquiries/{$reviewRoute->transport_enquiry_id}/route-resolutions/{$reviewRoute->id}/exceptions/manual-miles";

        $this->post($fallbackPath, [
            'reason_category' => 'disputed_mileage',
            'explanation' => 'The reviewed route needed recorded operational mile adjustments.',
            'route_legs' => [
                ['miles' => 12],
                ['miles' => 88],
                ['miles' => 99],
            ],
        ])->assertRedirect();

        $revision = JobRevision::query()->firstOrFail();

        $this->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/issue", [
            'issue_confirmation' => '1',
            'confirmed_revision_id' => $revision->id,
        ])->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}/issued");

        $this->assertNotNull($revision->fresh()->issued_at);
    }

    public function test_invalid_input_cannot_show_or_apply_manual_route_miles(): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createActivePricingContext();
        $invalidRoute = $this->createRouteResolution('invalid_input', false, [null, null, null]);
        $exceptionPath = "/transport-enquiries/{$invalidRoute->transport_enquiry_id}/route-resolutions/{$invalidRoute->id}/exceptions";

        $this->get($exceptionPath)
            ->assertOk()
            ->assertDontSeeText('Use manual miles because route lookup is unavailable')
            ->assertDontSee("{$exceptionPath}/manual-miles", false);

        $this->from($exceptionPath)
            ->post("{$exceptionPath}/manual-miles", [
                'reason_category' => 'postcode_ambiguity',
                'explanation' => 'The postcode needs correction before a route can be priced.',
                'route_legs' => [
                    ['miles' => 12],
                    ['miles' => 88],
                    ['miles' => 99],
                ],
            ])
            ->assertRedirect($exceptionPath)
            ->assertSessionHasErrors('route');

        $this->assertDatabaseCount('job_revisions', 0);
        $this->assertDatabaseCount('route_legs', 0);
        $this->assertDatabaseCount('quote_exception_audits', 0);
    }

    public function test_disputed_route_leg_override_creates_a_new_revision_with_original_and_replacement_miles(): void
    {
        $operator = User::factory()->create(['name' => 'Harriet Vale', 'can_manage_quote_exceptions' => true]);
        $this->actingAs($operator);
        $this->createActivePricingContext();
        $warningRoute = $this->createRouteResolution('resolved_with_warning', true, [10, 90, 96]);

        $this->post("/transport-enquiries/{$warningRoute->transport_enquiry_id}/route-resolutions/{$warningRoute->id}/accept")
            ->assertRedirect();

        $originalRevision = JobRevision::query()->firstOrFail();
        $exceptionPath = "/jobs/{$originalRevision->job_id}/revisions/{$originalRevision->id}/exceptions";

        $this->get($exceptionPath)
            ->assertOk()
            ->assertSeeText('Adjust a disputed route leg')
            ->assertSeeText('Original route value')
            ->assertSeeText('90');

        $this->post("{$exceptionPath}/route-legs", [
            'overrides' => [
                1 => [
                    'miles' => 84,
                    'reason_category' => 'disputed_mileage',
                    'explanation' => 'The regular equine route avoids the town centre.',
                ],
            ],
        ])->assertRedirect();

        $updatedRevision = JobRevision::query()->orderByDesc('revision_number')->firstOrFail();

        $this->assertSame(2, $updatedRevision->revision_number);
        $this->assertSame(90, $originalRevision->fresh()->routeLegs()->where('sequence', 2)->value('miles'));
        $this->assertNull($originalRevision->fresh()->routeLegs()->where('sequence', 2)->value('manual_miles'));
        $this->assertSame(84, $updatedRevision->routeLegs()->where('sequence', 2)->value('manual_miles'));
        $this->assertDatabaseHas('quote_exception_audits', [
            'job_revision_id' => $updatedRevision->id,
            'exception_type' => 'route_leg_override',
            'reason_category' => 'disputed_mileage',
            'explanation' => 'The regular equine route avoids the town centre.',
            'actor_id' => $operator->id,
            'original_value' => '90',
            'replacement_value' => '84',
        ]);
    }

    public function test_final_total_override_retains_the_engine_total_and_audits_the_commercial_decision(): void
    {
        $operator = User::factory()->create(['name' => 'Harriet Vale', 'can_manage_quote_exceptions' => true]);
        $this->actingAs($operator);
        $this->createActivePricingContext();
        $route = $this->createRouteResolution();

        $this->post("/transport-enquiries/{$route->transport_enquiry_id}/route-resolutions/{$route->id}/accept")
            ->assertRedirect();

        $originalRevision = JobRevision::query()->firstOrFail();
        $engineTotal = $originalRevision->engine_total;
        $exceptionPath = "/jobs/{$originalRevision->job_id}/revisions/{$originalRevision->id}/exceptions";

        $this->post("{$exceptionPath}/final-total", [
            'final_total' => '185.00',
            'reason_category' => 'commercial_adjustment',
            'explanation' => 'The customer received the agreed return-customer adjustment.',
        ])->assertRedirect();

        $overriddenRevision = JobRevision::query()->orderByDesc('revision_number')->firstOrFail();

        $this->assertSame($engineTotal, $overriddenRevision->engine_total);
        $this->assertSame('185.00', $overriddenRevision->final_total);
        $this->assertDatabaseHas('quote_exception_audits', [
            'job_revision_id' => $overriddenRevision->id,
            'exception_type' => 'final_total_override',
            'reason_category' => 'commercial_adjustment',
            'explanation' => 'The customer received the agreed return-customer adjustment.',
            'actor_id' => $operator->id,
            'original_value' => $engineTotal,
            'replacement_value' => '185.00',
        ]);

        $this->get("/jobs/{$overriddenRevision->job_id}/revisions/{$overriddenRevision->id}")
            ->assertOk()
            ->assertSeeText('Final total overridden')
            ->assertSeeText('Engine total')
            ->assertSeeText($engineTotal)
            ->assertSeeText('185.00');
    }

    public function test_final_total_overrides_require_exactly_two_decimal_places(): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createActivePricingContext();
        $route = $this->createRouteResolution();
        $this->post("/transport-enquiries/{$route->transport_enquiry_id}/route-resolutions/{$route->id}/accept");
        $revision = JobRevision::query()->firstOrFail();
        $exceptionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}/exceptions";

        $this->from($exceptionPath)
            ->post("{$exceptionPath}/final-total", [
                'final_total' => '185.1',
                'reason_category' => 'commercial_adjustment',
                'explanation' => 'The customer received the agreed return-customer adjustment.',
            ])
            ->assertRedirect($exceptionPath)
            ->assertSessionHasErrors('final_total');

        $this->assertDatabaseCount('job_revisions', 1);
        $this->assertDatabaseCount('quote_exception_audits', 0);
    }

    #[DataProvider('invalidControlledOverrideEvidence')]
    public function test_controlled_override_rejects_invalid_evidence(array $override, string $errorField): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createActivePricingContext();
        $route = $this->createRouteResolution();
        $this->post("/transport-enquiries/{$route->transport_enquiry_id}/route-resolutions/{$route->id}/accept");
        $revision = JobRevision::query()->firstOrFail();
        $exceptionPath = "/jobs/{$revision->job_id}/revisions/{$revision->id}/exceptions";

        $this->from($exceptionPath)
            ->post("{$exceptionPath}/final-total", $override)
            ->assertSessionHasErrors($errorField);

        $this->assertDatabaseCount('job_revisions', 1);
        $this->assertDatabaseCount('quote_exception_audits', 0);
    }

    public function test_route_repricing_clears_the_prior_final_total_override_from_the_new_revision(): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createActivePricingContext();
        $route = $this->createRouteResolution('resolved_with_warning', true, [10, 90, 96]);
        $this->post("/transport-enquiries/{$route->transport_enquiry_id}/route-resolutions/{$route->id}/accept");
        $initialRevision = JobRevision::query()->firstOrFail();

        $this->post("/jobs/{$initialRevision->job_id}/revisions/{$initialRevision->id}/exceptions/final-total", [
            'final_total' => '185.00',
            'reason_category' => 'commercial_adjustment',
            'explanation' => 'The customer received the agreed return-customer adjustment.',
        ]);
        $overriddenRevision = JobRevision::query()->orderByDesc('revision_number')->firstOrFail();

        $this->post("/jobs/{$overriddenRevision->job_id}/revisions/{$overriddenRevision->id}/exceptions/route-legs", [
            'overrides' => [
                1 => [
                    'miles' => 84,
                    'reason_category' => 'disputed_mileage',
                    'explanation' => 'The regular equine route avoids the town centre.',
                ],
            ],
        ])->assertRedirect();

        $repricedRevision = JobRevision::query()->orderByDesc('revision_number')->firstOrFail();

        $this->assertSame('185.00', $overriddenRevision->fresh()->final_total);
        $this->assertSame($repricedRevision->engine_total, $repricedRevision->final_total);
        $this->assertNull($repricedRevision->manual_final_total_reason);
        $this->assertSame([], $repricedRevision->calculation_explanation['overrides']);
        $this->assertFalse($repricedRevision->quoteExceptionAudits()->where('exception_type', 'final_total_override')->exists());
        $this->assertTrue($repricedRevision->quoteExceptionAudits()->where('exception_type', 'route_leg_override')->exists());
    }

    public function test_exception_requests_reject_unapproved_reason_categories(): void
    {
        $this->actingAs(User::factory()->create(['can_manage_quote_exceptions' => true]));
        $this->createActivePricingContext();
        $outageRoute = $this->createRouteResolution('provider_unavailable', false, [null, null, null]);
        $fallbackPath = "/transport-enquiries/{$outageRoute->transport_enquiry_id}/route-resolutions/{$outageRoute->id}/exceptions/manual-miles";

        $this->from("/transport-enquiries/{$outageRoute->transport_enquiry_id}/route-resolutions/{$outageRoute->id}/exceptions")
            ->post($fallbackPath, [
                'reason_category' => 'unapproved_category',
                'explanation' => 'The provider remained unavailable while the customer waited.',
                'route_legs' => [
                    ['miles' => 12],
                    ['miles' => 88],
                    ['miles' => 99],
                ],
            ])
            ->assertRedirect("/transport-enquiries/{$outageRoute->transport_enquiry_id}/route-resolutions/{$outageRoute->id}/exceptions")
            ->assertSessionHasErrors('reason_category');

        $route = $this->createRouteResolution();
        $this->post("/transport-enquiries/{$route->transport_enquiry_id}/route-resolutions/{$route->id}/accept")
            ->assertRedirect();
        $revision = JobRevision::query()->firstOrFail();

        $this->from("/jobs/{$revision->job_id}/revisions/{$revision->id}/exceptions")
            ->post("/jobs/{$revision->job_id}/revisions/{$revision->id}/exceptions/final-total", [
                'final_total' => '185.00',
                'reason_category' => 'unapproved_category',
                'explanation' => 'The customer received the agreed return-customer adjustment.',
            ])
            ->assertRedirect("/jobs/{$revision->job_id}/revisions/{$revision->id}/exceptions")
            ->assertSessionHasErrors('reason_category');

        $this->assertSame(1, JobRevision::query()->count());
    }

    public function test_returning_an_issued_route_quote_to_draft_clones_the_revision_and_preserves_the_issued_evidence(): void
    {
        $operator = User::factory()->create(['name' => 'Harriet Vale', 'can_manage_quote_exceptions' => true]);
        $this->actingAs($operator);
        $this->createActivePricingContext();
        $route = $this->createRouteResolution();

        $this->post("/transport-enquiries/{$route->transport_enquiry_id}/route-resolutions/{$route->id}/accept")
            ->assertRedirect();

        $initialRevision = JobRevision::query()->firstOrFail();

        $this->post("/jobs/{$initialRevision->job_id}/revisions/{$initialRevision->id}/exceptions/final-total", [
            'final_total' => '185.00',
            'reason_category' => 'commercial_adjustment',
            'explanation' => 'The customer received the agreed return-customer adjustment.',
        ])->assertRedirect();

        $issuedRevision = JobRevision::query()->orderByDesc('revision_number')->firstOrFail();
        $issuedLegValues = $issuedRevision->routeLegs()->orderBy('sequence')->pluck('miles')->all();
        $issuedAudit = $issuedRevision->quoteExceptionAudits()->firstOrFail();

        $this->post("/jobs/{$issuedRevision->job_id}/revisions/{$issuedRevision->id}/issue", [
            'issue_confirmation' => '1',
            'confirmed_revision_id' => $issuedRevision->id,
        ])
            ->assertRedirect();

        $this->post("/jobs/{$issuedRevision->job_id}/revisions/{$issuedRevision->id}/draft")
            ->assertRedirect();

        $draftRevision = JobRevision::query()->orderByDesc('revision_number')->firstOrFail();
        $job = $issuedRevision->job()->firstOrFail();

        $this->assertSame(3, JobRevision::query()->where('job_id', $issuedRevision->job_id)->count());
        $this->assertSame(3, $draftRevision->revision_number);
        $this->assertSame('draft', $job->status);
        $this->assertSame($issuedRevision->id, $job->issued_revision_id);
        $this->assertNotNull($job->issued_at);
        $this->assertSame($draftRevision->id, $job->current_working_revision_id);
        $this->assertSame($route->id, $issuedRevision->fresh()->route_resolution_id);
        $this->assertSame($issuedLegValues, $issuedRevision->fresh()->routeLegs()->orderBy('sequence')->pluck('miles')->all());
        $this->assertSame($issuedRevision->engine_total, $issuedRevision->fresh()->engine_total);
        $this->assertSame('185.00', $issuedRevision->fresh()->final_total);
        $this->assertSame($operator->id, $issuedAudit->actor_id);
        $this->assertSame('final_total_override', $issuedAudit->exception_type);
        $this->assertSame('commercial_adjustment', $issuedAudit->reason_category);
        $this->assertSame(2, \DB::table('quote_exception_audits')->where('exception_type', 'final_total_override')->count());
    }

    public static function invalidControlledOverrideEvidence(): array
    {
        return [
            'unapproved category' => [[
                'final_total' => '185.00',
                'reason_category' => 'unapproved_category',
                'explanation' => 'The customer received the agreed adjustment.',
            ], 'reason_category'],
            'missing explanation' => [[
                'final_total' => '185.00',
                'reason_category' => 'commercial_adjustment',
                'explanation' => '',
            ], 'explanation'],
            'non-positive amount' => [[
                'final_total' => '0.00',
                'reason_category' => 'commercial_adjustment',
                'explanation' => 'The customer received the agreed adjustment.',
            ], 'final_total'],
        ];
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

    private function createRouteResolution(
        string $status = 'resolved',
        bool $pricingEligible = true,
        array $miles = [10, 90, 96],
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
            'warnings' => $status === 'resolved_with_warning' ? [[
                'code' => 'mileage_review',
                'message' => 'Provider mileage needs review.',
                'context' => [],
            ]] : [],
            'failure_detail' => $status === 'provider_unavailable' ? [
                'code' => 'provider_unavailable',
                'message' => 'Provider unavailable.',
                'context' => [],
            ] : null,
            'resolved_at' => $status === 'provider_unavailable' ? null : now(),
            'attempted_at' => now(),
            'request_started_at' => now(),
            'response_received_at' => now(),
            'latency_milliseconds' => 100,
        ]);

        foreach ([
            ['depot_to_pickup', 'EX16 0AA', 'EX1 1AA'],
            ['pickup_to_drop_off', 'EX1 1AA', 'TA1 1AA'],
            ['drop_off_to_depot', 'TA1 1AA', 'EX16 0AA'],
        ] as $index => [$legType, $origin, $destination]) {
            RouteResolutionLeg::query()->create([
                'route_resolution_id' => $resolution->id,
                'sequence' => $index + 1,
                'leg_type' => $legType,
                'origin_input' => $origin,
                'destination_input' => $destination,
                'status' => $miles[$index] === null ? 'unresolved' : 'resolved',
                'distance_metres' => $miles[$index] === null ? null : $miles[$index] * 1609,
                'quoted_miles' => $miles[$index],
                'duration_seconds' => $miles[$index] === null ? null : 600,
                'provider_route_id' => $miles[$index] === null ? null : 'route-123',
                'warnings' => [],
                'failure_detail' => $miles[$index] === null ? [
                    'code' => 'provider_unavailable',
                    'message' => 'Provider unavailable.',
                    'context' => [],
                ] : null,
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
                $postcodes = ['EX16 0AA', 'EX2 4AB', 'TA2 8QQ'];
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
