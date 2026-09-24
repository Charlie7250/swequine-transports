<?php

namespace Tests\Feature\Dashboard;

use App\Models\Customer;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\LoadingPracticeQuote;
use App\Models\QuoteExceptionAudit;
use App\Models\RouteResolution;
use App\Models\TransportDay;
use App\Models\TransportEnquiry;
use App\Models\User;
use App\Services\Reporting\ReleaseOneOperationalEvidenceBuilder;
use App\Services\Scheduling\TransportDayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PipelineDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_scoped_operational_summary_metrics(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createJobWithRevision('draft', '111.00');
        $this->createJobWithRevision('quoted', '120.00', [
            'issued_at' => now()->subDays(4),
        ]);
        $this->createJobWithRevision('pending', '130.00', [
            'issued_at' => now()->subDays(3),
        ]);
        $this->createJobWithRevision('booked', '140.00', [
            'issued_at' => now()->subDays(2),
            'booked_at' => now()->subDay(),
        ]);
        $this->createJobWithRevision('completed', '150.00', [
            'issued_at' => now()->subDays(5),
            'booked_at' => now()->subDays(2),
            'completed_at' => now()->subHours(12),
        ]);

        LoadingPracticeQuote::query()->create([
            'customer_id' => Customer::query()->create(['name' => 'Separate loading practice customer'])->id,
            'status' => 'draft',
            'package_price' => '999.00',
            'is_poa' => false,
        ]);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('<p class="dashboard-summary-label">Today</p>', false);
        $response->assertSee('<p class="dashboard-summary-label">Awaiting response</p>', false);
        $response->assertSeeText('Pending now');
        $response->assertSeeText('Booked ahead');
        $response->assertSeeText('Next 7 days');
        $response->assertSee('<p class="dashboard-summary-label">Completed</p>', false);
        $response->assertSeeText('Last 7 days');
        $response->assertSeeText('Open quoted value');
        $response->assertSeeText('£250');
        $response->assertDontSeeText('£999');
    }

    public function test_dashboard_uses_lifecycle_revisions_for_operational_jobs_and_open_value(): void
    {
        $this->actingAs(User::factory()->create());

        $quotedCustomer = Customer::query()->create([
            'name' => 'Quoted customer',
        ]);
        $quotedJob = Job::query()->create([
            'customer_id' => $quotedCustomer->id,
            'status' => 'quoted',
            'issued_at' => now()->subDay(),
        ]);
        $quotedIssuedRevision = JobRevision::query()->create([
            'job_id' => $quotedJob->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
            'engine_total' => '120.00',
            'final_total' => '120.00',
        ]);
        $quotedDraftRevision = JobRevision::query()->create([
            'job_id' => $quotedJob->id,
            'revision_number' => 2,
            'horse_count' => 1,
            'pickup_postcode' => 'EX9 9ZZ',
            'dropoff_postcode' => 'TA9 9ZZ',
            'engine_total' => '999.00',
            'final_total' => '999.00',
        ]);
        $quotedJob->forceFill([
            'issued_revision_id' => $quotedIssuedRevision->id,
            'current_working_revision_id' => $quotedDraftRevision->id,
        ])->save();

        $brokenQuotedCustomer = Customer::query()->create([
            'name' => 'Broken quoted customer',
        ]);
        $brokenQuotedJob = Job::query()->create([
            'customer_id' => $brokenQuotedCustomer->id,
            'status' => 'quoted',
            'issued_at' => now()->subHours(12),
        ]);
        $brokenQuotedRevision = JobRevision::query()->create([
            'job_id' => $brokenQuotedJob->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX7 7ZZ',
            'dropoff_postcode' => 'TA7 7ZZ',
            'engine_total' => '500.00',
            'final_total' => '500.00',
        ]);
        $brokenQuotedJob->forceFill([
            'current_working_revision_id' => $brokenQuotedRevision->id,
            'issued_revision_id' => null,
        ])->save();

        $bookedCustomer = Customer::query()->create([
            'name' => 'Booked customer',
        ]);
        $bookedJob = Job::query()->create([
            'customer_id' => $bookedCustomer->id,
            'status' => 'booked',
            'issued_at' => now()->subDays(2),
            'booked_at' => now()->subDay(),
        ]);
        $bookedIssuedRevision = JobRevision::query()->create([
            'job_id' => $bookedJob->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX2 2AA',
            'dropoff_postcode' => 'TA2 2AA',
            'engine_total' => '140.00',
            'final_total' => '140.00',
        ]);
        $bookedAcceptedRevision = JobRevision::query()->create([
            'job_id' => $bookedJob->id,
            'revision_number' => 2,
            'horse_count' => 1,
            'pickup_postcode' => 'EX3 3AA',
            'dropoff_postcode' => 'TA3 3AA',
            'engine_total' => '180.00',
            'final_total' => '180.00',
        ]);
        $bookedDraftRevision = JobRevision::query()->create([
            'job_id' => $bookedJob->id,
            'revision_number' => 3,
            'horse_count' => 1,
            'pickup_postcode' => 'EX8 8ZZ',
            'dropoff_postcode' => 'TA8 8ZZ',
            'engine_total' => '888.00',
            'final_total' => '888.00',
        ]);
        $bookedJob->forceFill([
            'issued_revision_id' => $bookedIssuedRevision->id,
            'accepted_revision_id' => $bookedAcceptedRevision->id,
            'current_working_revision_id' => $bookedDraftRevision->id,
        ])->save();

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSeeText('£120');
        $response->assertSeeText('Collect 1 horse for Quoted customer');
        $response->assertSeeText('Collection: EX1 1AA. Drop-off: TA1 1AA.');
        $response->assertDontSeeText('EX9 9ZZ');
        $response->assertSeeText('Collect 1 horse for Booked customer');
        $response->assertSeeText('Collection: EX3 3AA. Drop-off: TA3 3AA.');
        $response->assertDontSeeText('EX8 8ZZ');
        $response->assertDontSeeText('EX7 7ZZ');
        $response->assertDontSeeText('£500');
        $response->assertDontSeeText('£999');
        $response->assertDontSeeText('£888');
    }

    public function test_dashboard_summary_is_compact_and_excludes_administrative_evidence(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Dashboard')
            ->assertDontSeeText("Today's transport plan")
            ->assertSee('<p class="dashboard-summary-label">Today</p>', false)
            ->assertSeeText('Open quoted value')
            ->assertDontSeeText('Route attempts')
            ->assertDontSeeText('Active fuel input')
            ->assertDontSeeText('Active rate setting');
    }

    public function test_dashboard_groups_jobs_under_their_transport_day_and_lists_unassigned_jobs_separately(): void
    {
        $this->actingAs(User::factory()->create());

        $day = TransportDay::query()->create([
            'run_date' => now()->toDateString(),
            'name' => 'Monday collection run',
        ]);
        $manager = app(TransportDayManager::class);
        $first = $this->jobWithRevision('Scheduled Alice', 'EX1 1AA', 'TA1 1AA');
        $second = $this->jobWithRevision('Scheduled Bob', 'EX2 2AA', 'TA2 2AA');
        $manager->addJob($day, $first);
        $manager->addJob($day, $second);
        $this->jobWithRevision('Unassigned Carol', 'EX3 3AA', 'TA3 3AA');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Upcoming transport days')
            ->assertSeeText('Monday collection run')
            ->assertSeeTextInOrder(['Scheduled Alice', 'Scheduled Bob'])
            ->assertSeeText('Unassigned work')
            ->assertSeeText('Unassigned Carol');
    }

    private function jobWithRevision(string $customerName, string $pickup, string $dropoff): Job
    {
        $job = Job::query()->create([
            'customer_id' => Customer::query()->create(['name' => $customerName])->id,
            'status' => 'draft',
        ]);
        $revision = JobRevision::query()->create([
            'job_id' => $job->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => $pickup,
            'dropoff_postcode' => $dropoff,
            'engine_total' => '100.00',
            'final_total' => '100.00',
        ]);
        $job->forceFill(['current_working_revision_id' => $revision->id])->save();

        return $job;
    }

    public function test_dashboard_keeps_operational_evidence_out_of_the_primary_workflow(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertDontSeeText('Route attempts')
            ->assertDontSeeText('No transport exception reasons recorded.')
            ->assertDontSeeText('Future intake boundaries')
            ->assertDontSeeText('Developer telemetry');

        $this->get('/admin/operational-evidence')
            ->assertOk()
            ->assertSeeText('Operational evidence')
            ->assertSeeText('Route attempts')
            ->assertSeeText('No transport exception reasons recorded.');
    }

    public function test_dashboard_reports_secret_free_route_evidence_with_deduplicated_rates_and_even_medians(): void
    {
        $this->actingAs(User::factory()->create());
        $actor = User::factory()->create();
        $first = $this->createOperationalChain(Carbon::create(2026, 9, 1, 10, 0, 0), 10, 40, 'provider_unavailable', ['code' => 'private_provider_detail'], true, $actor);
        $second = $this->createOperationalChain(Carbon::create(2026, 9, 1, 11, 0, 0), 11, 41, 'resolved', null, false, $actor);
        RouteResolution::query()->create([
            'transport_enquiry_id' => $first['enquiry']->id,
            'overall_status' => 'provider_response_invalid',
            'failure_detail' => ['code' => 'provider_response_invalid'],
        ]);

        $evidence = app(ReleaseOneOperationalEvidenceBuilder::class)->build();

        $this->assertSame(3, $evidence['attempts']['total']);
        $this->assertSame(1, collect($evidence['attempts']['failure_categories'])->firstWhere('category', 'provider_unavailable')['count']);
        $this->assertSame(1, $evidence['rates']['manual_route_fallback']['numerator']);
        $this->assertSame(2, $evidence['rates']['manual_route_fallback']['denominator']);
        $this->assertSame(50.0, $evidence['rates']['manual_route_fallback']['percentage']);
        $this->assertSame(11, $evidence['turnaround']['quote_ready']['median_seconds']);
        $this->assertSame(41, $evidence['turnaround']['issued']['median_seconds']);

        $this->get('/admin/operational-evidence')
            ->assertOk()
            ->assertSeeText('Operational evidence')
            ->assertSeeText('provider_unavailable: 1')
            ->assertDontSeeText('private_provider_detail');
    }

    public function test_dashboard_labels_beta_operational_evidence_with_exception_reason_summary(): void
    {
        $this->actingAs(User::factory()->create());
        $actor = User::factory()->create();
        $chain = $this->createOperationalChain(Carbon::create(2026, 9, 2, 9, 0, 0), 15, 45, 'resolved', null, false, $actor);

        $sourceJob = Job::query()->create([
            'customer_id' => Customer::query()->create(['name' => 'Non route-first source customer'])->id,
            'status' => 'quoted',
            'issued_at' => now()->subHour(),
        ]);
        $targetJob = Job::query()->create([
            'customer_id' => Customer::query()->create(['name' => 'Non route-first target customer'])->id,
            'status' => 'quoted',
            'issued_at' => now()->subMinutes(30),
        ]);
        $nonRouteFirstEnquiry = TransportEnquiry::query()->create([
            'customer_name' => 'Non route-first target customer',
            'pickup_postcode' => 'EX4 4AA',
            'dropoff_postcode' => 'TA4 4AA',
            'quote_job_id' => $sourceJob->id,
            'created_at' => now()->subMinutes(45),
        ]);
        $nonRouteFirstResolution = RouteResolution::query()->create([
            'transport_enquiry_id' => $nonRouteFirstEnquiry->id,
            'overall_status' => 'resolved',
            'failure_detail' => null,
        ]);
        $nonRouteFirstRevision = JobRevision::query()->create([
            'job_id' => $targetJob->id,
            'route_resolution_id' => $nonRouteFirstResolution->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX4 4AA',
            'dropoff_postcode' => 'TA4 4AA',
            'created_at' => now()->subMinutes(40),
        ]);

        $rootAlphaAudit = QuoteExceptionAudit::query()->create([
            'job_revision_id' => $chain['revision']->id,
            'actor_id' => $actor->id,
            'exception_type' => 'manual_route_fallback',
            'reason_category' => ' alpha ',
            'explanation' => 'Root audit alpha.',
            'replacement_value' => 'manual',
            'recorded_at' => now(),
            'source_quote_exception_audit_id' => null,
        ]);
        QuoteExceptionAudit::query()->create([
            'job_revision_id' => $chain['revision']->id,
            'actor_id' => $actor->id,
            'exception_type' => 'manual_route_fallback',
            'reason_category' => 'alpha',
            'explanation' => 'Copied audit alpha.',
            'replacement_value' => 'manual',
            'recorded_at' => now()->addMinute(),
            'source_quote_exception_audit_id' => $rootAlphaAudit->id,
        ]);
        QuoteExceptionAudit::query()->create([
            'job_revision_id' => $chain['revision']->id,
            'actor_id' => $actor->id,
            'exception_type' => 'manual_route_fallback',
            'reason_category' => '   ',
            'explanation' => 'Root audit blank.',
            'replacement_value' => 'manual',
            'recorded_at' => now()->addMinutes(2),
            'source_quote_exception_audit_id' => null,
        ]);
        QuoteExceptionAudit::query()->create([
            'job_revision_id' => $nonRouteFirstRevision->id,
            'actor_id' => $actor->id,
            'exception_type' => 'manual_route_fallback',
            'reason_category' => 'non_route_first_only',
            'explanation' => 'Non route-first audit.',
            'replacement_value' => 'manual',
            'recorded_at' => now()->addMinutes(3),
            'source_quote_exception_audit_id' => null,
        ]);

        $evidence = app(ReleaseOneOperationalEvidenceBuilder::class)->build();

        $this->assertArrayHasKey('exception_reasons', $evidence);
        $this->assertSame([
            ['category' => 'alpha', 'count' => 1],
            ['category' => 'unknown', 'count' => 1],
        ], $evidence['exception_reasons']);

        $this->get('/admin/operational-evidence')
            ->assertOk()
            ->assertSeeText('Operational evidence')
            ->assertSeeText('Exception reasons')
            ->assertSeeText('alpha: 1')
            ->assertSeeText('unknown: 1');
    }

    public function test_dashboard_shows_statuses_for_actionable_unassigned_jobs(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createJobWithRevision('draft', '111.00');
        $this->createJobWithRevision('booked', '140.00', [
            'issued_at' => now()->subDays(2),
            'booked_at' => now()->subDay(),
        ]);
        $this->createJobWithRevision('lost', '90.00');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Draft')
            ->assertSeeText('Booked')
            ->assertDontSeeText('Lost customer');
    }

    public function test_dashboard_lists_unassigned_jobs_with_clear_collection_and_drop_off_context(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createJobWithRevision('draft', '111.00');
        $this->createJobWithRevision('booked', '140.00', [
            'issued_at' => now()->subDays(2),
            'booked_at' => now()->subDay(),
        ]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Unassigned work')
            ->assertSeeText('Draft customer')
            ->assertSeeText('Booked customer')
            ->assertSeeText('Collection: EX1 1AA. Drop-off: TA1 1AA.');
    }

    private function createOperationalChain(
        Carbon $createdAt,
        int $quoteReadySeconds,
        int $issuedSeconds,
        string $status,
        ?array $failureDetail = null,
        bool $withManualFallback = false,
        ?User $actor = null,
    ): array {
        $job = Job::query()->create([
            'customer_id' => Customer::query()->create(['name' => 'Operational customer'])->id,
            'status' => 'quoted',
            'issued_at' => $createdAt->copy()->addSeconds($issuedSeconds),
        ]);
        $enquiry = TransportEnquiry::query()->create([
            'customer_name' => 'Operational customer',
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
            'quote_job_id' => $job->id,
            'created_at' => $createdAt,
        ]);
        $resolution = RouteResolution::query()->create([
            'transport_enquiry_id' => $enquiry->id,
            'overall_status' => $status,
            'failure_detail' => $failureDetail,
        ]);
        $revision = JobRevision::query()->create([
            'job_id' => $job->id,
            'route_resolution_id' => $resolution->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
            'created_at' => $createdAt->copy()->addSeconds($quoteReadySeconds),
        ]);

        $job->forceFill([
            'issued_revision_id' => $revision->id,
            'current_working_revision_id' => $revision->id,
        ])->save();

        if ($withManualFallback && $actor !== null) {
            $auditAttributes = [
                'job_revision_id' => $revision->id,
                'actor_id' => $actor->id,
                'exception_type' => 'manual_route_fallback',
                'reason_category' => 'provider_outage',
                'explanation' => 'Fallback used for operational evidence.',
                'replacement_value' => 'manual',
                'recorded_at' => $createdAt->copy()->addSeconds($quoteReadySeconds),
            ];
            QuoteExceptionAudit::query()->create($auditAttributes);
            QuoteExceptionAudit::query()->create($auditAttributes);
        }

        return ['enquiry' => $enquiry, 'resolution' => $resolution, 'revision' => $revision];
    }

    private function createJobWithRevision(string $status, string $finalTotal, array $jobOverrides = []): void
    {
        $customer = Customer::query()->create([
            'name' => ucfirst($status).' customer',
        ]);

        $job = Job::query()->create(array_merge([
            'customer_id' => $customer->id,
            'status' => $status,
        ], $jobOverrides));

        $revision = JobRevision::query()->create([
            'job_id' => $job->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
            'engine_total' => $finalTotal,
            'final_total' => $finalTotal,
        ]);

        $job->forceFill([
            'current_working_revision_id' => $revision->id,
            'issued_revision_id' => $job->issued_at ? $revision->id : null,
            'accepted_revision_id' => $job->booked_at ? $revision->id : null,
        ])->save();
    }
}
