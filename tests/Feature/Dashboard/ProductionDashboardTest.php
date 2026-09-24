<?php

namespace Tests\Feature\Dashboard;

use App\Models\Customer;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\RouteLeg;
use App\Models\RouteResolution;
use App\Models\RouteResolutionLeg;
use App\Models\TransportDay;
use App\Models\TransportEnquiry;
use App\Models\User;
use App\Services\Reporting\OperatorDashboardBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProductionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 9, 21, 9, 0, 0, 'Europe/London'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_summarises_the_current_operational_window(): void
    {
        $this->actingAs(User::factory()->create());
        $today = TransportDay::query()->create(['run_date' => '2026-09-21']);
        $future = TransportDay::query()->create(['run_date' => '2026-09-24']);

        $this->createJob('booked', 'Booked today', '100.00', $today, ['booked_at' => now()]);
        $this->createJob('pending', 'Pending today', '285.00', $today, ['issued_at' => now()]);
        $this->createJob('quoted', 'Quoted today', '340.00', $today, ['issued_at' => now()]);
        $this->createJob('booked', 'Booked ahead', '410.00', $future, ['booked_at' => now()]);
        $this->createJob('completed', 'Completed recently', '500.00', null, ['completed_at' => now()->subDays(2)]);
        $this->createJob('completed', 'Completed earlier', '600.00', null, ['completed_at' => now()->subDays(8)]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('<p class="dashboard-summary-label">Today</p>', false)
            ->assertSeeText('1 day')
            ->assertSeeText('3 jobs scheduled today')
            ->assertSeeText('1 quote')
            ->assertSee('<p class="dashboard-summary-label">Awaiting response</p>', false)
            ->assertSeeText('1 job')
            ->assertSee('<p class="dashboard-summary-label">Booked ahead</p>', false)
            ->assertSee('<p class="dashboard-summary-label">Completed</p>', false)
            ->assertSeeText('£625')
            ->assertSee('<p class="dashboard-summary-label">Open quoted value</p>', false)
            ->assertSeeText('Pending and quoted jobs')
            ->assertDontSee('<p class="dashboard-summary-label">Scheduled today</p>', false)
            ->assertDontSee('<p class="dashboard-summary-label">Awaiting decisions</p>', false)
            ->assertDontSee('<p class="dashboard-summary-label">Completed recently</p>', false);
    }

    public function test_dashboard_uses_a_compact_header_without_old_welcome_copy(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Morgan Reed']));

        $response = $this->get('/dashboard');

        $response->assertOk()
            ->assertSeeText('Dashboard')
            ->assertSeeText('Monday 21 September 2026')
            ->assertDontSeeText("Today's transport plan")
            ->assertDontSeeText('Welcome back, Morgan.')
            ->assertDontSeeText('Your transport schedule, quote follow-ups, and next useful actions in one place.');

        $this->assertSame(1, substr_count($response->getContent(), '<h1>Dashboard</h1>'));
    }

    public function test_dashboard_renders_ordered_jobs_with_route_evidence(): void
    {
        $this->actingAs(User::factory()->create());
        $day = TransportDay::query()->create([
            'run_date' => '2026-09-21',
            'name' => 'Devon route',
            'depot_postcode' => 'EX15 1AA',
        ]);
        $first = $this->createJob('pending', 'First customer', '285.00', $day, ['issued_at' => now()], 2);
        $second = $this->createJob('booked', 'Second customer', '410.00', $day, ['booked_at' => now()], 1);
        $this->attachRouteEvidence($first['revision'], [10, 20, 30], [600, 900, 1200]);
        $this->attachRouteEvidence($second['revision'], [5, 10, 15], [300, 600, 900]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Devon route')
            ->assertSeeTextInOrder(['First customer', 'Second customer'])
            ->assertSeeText('Collect 2 horses')
            ->assertSeeText('Collection: EX1 1AA')
            ->assertSeeText('Drop-off: TA1 1AA')
            ->assertSeeText('90 quoted miles')
            ->assertSeeText('1 hr 15 min')
            ->assertSee(route('jobs.revisions.show', ['job' => $first['job'], 'revision' => $first['revision']]), false);
    }

    public function test_dashboard_uses_manual_route_leg_overrides_for_mileage(): void
    {
        $this->actingAs(User::factory()->create());
        $day = TransportDay::query()->create(['run_date' => '2026-09-21']);
        $job = $this->createJob('quoted', 'Override customer', '300.00', $day, ['issued_at' => now()]);
        $this->attachRouteEvidence($job['revision'], [10, 20, 30], [600, 900, 1200]);
        $job['revision']->routeLegs()->where('sequence', 2)->update(['manual_miles' => 25]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('1 job scheduled today')
            ->assertSeeText('65 quoted miles')
            ->assertSeeText('45 min');
    }

    public function test_dashboard_keeps_duration_when_provider_mileage_is_missing(): void
    {
        $this->actingAs(User::factory()->create());
        $day = TransportDay::query()->create(['run_date' => '2026-09-21']);
        $job = $this->createJob('draft', 'Duration customer', '300.00', $day);
        $this->attachDurationOnlyRouteEvidence($job['revision'], [600, 900, 300]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('30 min')
            ->assertSee('class="transport-day-fact transport-day-fact--duration"', false)
            ->assertDontSee('transport-day-fact--miles', false)
            ->assertDontSeeText('quoted miles')
            ->assertDontSeeText('Mileage unavailable');
    }

    public function test_dashboard_shows_manual_route_mileage_without_provider_duration(): void
    {
        $this->actingAs(User::factory()->create());
        $day = TransportDay::query()->create(['run_date' => '2026-09-21']);
        $job = $this->createJob('draft', 'Manual route customer', '300.00', $day);
        $this->attachStoredRouteLegs($job['revision'], [12, 34, 56]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('102 quoted miles')
            ->assertDontSeeText('Duration unavailable');
    }

    public function test_dashboard_omits_mileage_when_stored_route_has_partial_legs(): void
    {
        $this->actingAs(User::factory()->create());
        $day = TransportDay::query()->create(['run_date' => '2026-09-21']);
        $job = $this->createJob('draft', 'Partial mileage customer', '300.00', $day);
        $this->attachStoredRouteLegs($job['revision'], [12]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertDontSee('transport-day-fact--miles', false)
            ->assertDontSeeText('quoted miles');
    }

    public function test_dashboard_omits_duration_when_provider_route_has_partial_legs(): void
    {
        $this->actingAs(User::factory()->create());
        $day = TransportDay::query()->create(['run_date' => '2026-09-21']);
        $job = $this->createJob('draft', 'Partial duration customer', '300.00', $day);
        $this->attachDurationOnlyRouteEvidence($job['revision'], [600]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertDontSee('transport-day-fact--duration', false)
            ->assertDontSeeText('10 min');
    }

    public function test_booked_ahead_covers_the_next_seven_complete_dates(): void
    {
        $this->actingAs(User::factory()->create());
        $today = TransportDay::query()->create(['run_date' => '2026-09-21']);
        $tomorrow = TransportDay::query()->create(['run_date' => '2026-09-22']);
        $daySeven = TransportDay::query()->create(['run_date' => '2026-09-28']);
        $dayEight = TransportDay::query()->create(['run_date' => '2026-09-29']);

        $this->createJob('booked', 'Today', '100.00', $today, ['booked_at' => now()]);
        $this->createJob('booked', 'Tomorrow', '100.00', $tomorrow, ['booked_at' => now()]);
        $this->createJob('booked', 'Day seven', '100.00', $daySeven, ['booked_at' => now()]);
        $this->createJob('booked', 'Day eight', '100.00', $dayEight, ['booked_at' => now()]);

        $summary = app(OperatorDashboardBuilder::class)->build()['summary'];

        $this->assertSame('2 jobs', $summary[2]['value']);
    }

    public function test_dashboard_keeps_quiet_days_and_actionable_unassigned_work_visible(): void
    {
        $this->actingAs(User::factory()->create());
        TransportDay::query()->create(['run_date' => '2026-09-22', 'name' => 'Available day']);
        $unassigned = $this->createJob('draft', 'Unassigned customer', '100.00');
        $this->createJob('lost', 'Lost customer', '100.00');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Available day')
            ->assertSeeText('No jobs assigned yet - available for new work.')
            ->assertDontSee('class="transport-day-empty-icon"', false)
            ->assertSeeText('Unassigned customer')
            ->assertSee(route('jobs.revisions.show', [
                'job' => $unassigned['job'],
                'revision' => $unassigned['revision'],
            ]), false)
            ->assertDontSeeText('Lost customer');
    }

    public function test_dashboard_omits_unavailable_day_metrics_and_shows_total_horses(): void
    {
        $this->actingAs(User::factory()->create());
        $day = TransportDay::query()->create(['run_date' => '2026-09-21']);
        $this->createJob('draft', 'Horse customer', '300.00', $day, [], 2);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('1 job')
            ->assertSee('class="transport-day-fact transport-day-fact--jobs"', false)
            ->assertSee('class="transport-day-fact transport-day-fact--horses"', false)
            ->assertDontSee('transport-day-fact--miles', false)
            ->assertDontSee('transport-day-fact--duration', false)
            ->assertDontSeeText('Mileage unavailable')
            ->assertDontSeeText('Duration unavailable');
    }

    public function test_dashboard_keeps_controls_and_removes_working_week_kicker(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Upcoming transport days')
            ->assertSeeText('View all transport days')
            ->assertSeeText('21 - 27 Sep 2026')
            ->assertSee('aria-pressed="true">Week</button>', false)
            ->assertDontSeeText('Working week');
    }

    public function test_dashboard_uses_a_compact_unassigned_state_when_no_work_needs_planning(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/dashboard');

        $response->assertOk()
            ->assertSeeText('Unassigned work')
            ->assertSeeText('0')
            ->assertDontSeeText('No unassigned transport work needs planning.')
            ->assertDontSee('class="unassigned-list"', false);
    }

    public function test_authenticated_pages_share_the_operator_navigation(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Morgan Reed']));

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Dashboard')
            ->assertSeeText('Monday 21 September 2026')
            ->assertDontSeeText("Today's transport plan")
            ->assertDontSeeText('Welcome back, Morgan.')
            ->assertSee('class="operator-sidebar"', false)
            ->assertSeeText('Administration');

        $this->get('/transport-days')
            ->assertOk()
            ->assertSee('class="operator-sidebar"', false)
            ->assertSeeText('Administration');
    }

    public function test_dashboard_displays_the_visual_date_scope_controls(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('21 - 27 Sep 2026')
            ->assertSee('aria-pressed="true">Week</button>', false);
    }

    public function test_mobile_header_keeps_the_sign_out_action_visible(): void
    {
        $stylesheet = file_get_contents(resource_path('css/dashboard-prototype.css'));

        $this->assertDoesNotMatchRegularExpression(
            '/\.operator-sign-out,\s*\.operator-topbar-label\s*\{\s*display:\s*none;/',
            $stylesheet,
        );
    }

    public function test_operational_day_typography_uses_the_sans_ui_stack(): void
    {
        $stylesheet = file_get_contents(resource_path('css/dashboard-prototype.css'));

        $this->assertMatchesRegularExpression(
            '/\.transport-day-title strong\s*\{[^}]*font-family: "Instrument Sans", "Segoe UI", sans-serif;/s',
            $stylesheet,
        );
        $this->assertMatchesRegularExpression(
            '/\.transport-day-facts\s*\{[^}]*font-family: "Instrument Sans", "Segoe UI", sans-serif;/s',
            $stylesheet,
        );
        $this->assertMatchesRegularExpression(
            '/\.dashboard-date-meta\s*\{[^}]*font-family: "Instrument Sans", "Segoe UI", sans-serif;/s',
            $stylesheet,
        );
        $this->assertMatchesRegularExpression(
            '/\.dashboard-date-stepper span\s*\{[^}]*font-family: "Instrument Sans", "Segoe UI", sans-serif;/s',
            $stylesheet,
        );
        $this->assertMatchesRegularExpression(
            '/\.dashboard-date-views button\s*\{[^}]*font-family: "Instrument Sans", "Segoe UI", sans-serif;/s',
            $stylesheet,
        );
    }

    private function createJob(
        string $status,
        string $customerName,
        string $total,
        ?TransportDay $day = null,
        array $timestamps = [],
        int $horseCount = 1,
    ): array {
        $job = Job::query()->create(array_merge([
            'customer_id' => Customer::query()->create(['name' => $customerName])->id,
            'status' => $status,
            'transport_day_id' => $day?->id,
            'transport_day_sequence' => $day === null ? null : $day->jobs()->count() + 1,
        ], $timestamps));
        $revision = JobRevision::query()->create([
            'job_id' => $job->id,
            'revision_number' => 1,
            'horse_count' => $horseCount,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
            'engine_total' => $total,
            'final_total' => $total,
        ]);
        $job->forceFill($this->revisionLinks($status, $revision))->save();

        return ['job' => $job, 'revision' => $revision];
    }

    private function revisionLinks(string $status, JobRevision $revision): array
    {
        return [
            'current_working_revision_id' => $revision->id,
            'issued_revision_id' => in_array($status, ['quoted', 'pending'], true) ? $revision->id : null,
            'accepted_revision_id' => in_array($status, ['booked', 'completed'], true) ? $revision->id : null,
        ];
    }

    private function attachRouteEvidence(JobRevision $revision, array $miles, array $durations): void
    {
        $this->attachStoredRouteLegs($revision, $miles);
        $enquiry = TransportEnquiry::query()->create([
            'customer_name' => $revision->job->customer->name,
            'pickup_postcode' => $revision->pickup_postcode,
            'dropoff_postcode' => $revision->dropoff_postcode,
        ]);
        $resolution = RouteResolution::query()->create([
            'transport_enquiry_id' => $enquiry->id,
            'overall_status' => 'resolved',
        ]);
        foreach ($miles as $index => $quotedMiles) {
            RouteResolutionLeg::query()->create([
                'route_resolution_id' => $resolution->id,
                'sequence' => $index + 1,
                'leg_type' => 'leg_'.$index,
                'origin_input' => 'Origin',
                'destination_input' => 'Destination',
                'status' => 'resolved',
                'quoted_miles' => $quotedMiles,
                'duration_seconds' => $durations[$index],
            ]);
        }
        $revision->forceFill(['route_resolution_id' => $resolution->id])->save();
    }

    private function attachStoredRouteLegs(JobRevision $revision, array $miles): void
    {
        foreach ($miles as $index => $quotedMiles) {
            RouteLeg::query()->create([
                'job_revision_id' => $revision->id,
                'sequence' => $index + 1,
                'label' => ['depot_to_pickup', 'pickup_to_dropoff', 'dropoff_to_depot'][$index],
                'miles' => $quotedMiles,
                'rate_type' => $index === 1 ? 'loaded' : 'unloaded',
            ]);
        }
    }

    private function attachDurationOnlyRouteEvidence(JobRevision $revision, array $durations): void
    {
        $enquiry = TransportEnquiry::query()->create([
            'customer_name' => $revision->job->customer->name,
            'pickup_postcode' => $revision->pickup_postcode,
            'dropoff_postcode' => $revision->dropoff_postcode,
        ]);
        $resolution = RouteResolution::query()->create([
            'transport_enquiry_id' => $enquiry->id,
            'overall_status' => 'resolved',
        ]);
        foreach ($durations as $index => $duration) {
            RouteResolutionLeg::query()->create([
                'route_resolution_id' => $resolution->id,
                'sequence' => $index + 1,
                'leg_type' => 'leg_'.$index,
                'origin_input' => 'Origin',
                'destination_input' => 'Destination',
                'status' => 'resolved',
                'quoted_miles' => null,
                'duration_seconds' => $duration,
            ]);
        }
        $revision->forceFill(['route_resolution_id' => $resolution->id])->save();
    }
}
