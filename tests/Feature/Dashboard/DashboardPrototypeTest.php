<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Support\DashboardPrototypeFixture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPrototypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_dashboard_prototype(): void
    {
        $this->get('/prototype/dashboard')
            ->assertRedirect('/login');
    }

    public function test_authenticated_staff_can_view_the_fixture_backed_dashboard_prototype(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Morgan Reed']));

        $response = $this->get('/prototype/dashboard');

        $response
            ->assertOk()
            ->assertSeeText('Dashboard')
            ->assertSeeText('Sunday 20 September 2026')
            ->assertDontSeeText("Today's transport plan")
            ->assertDontSeeText('Welcome back, Morgan.')
            ->assertDontSeeText('Your transport schedule, quote follow-ups, and next useful actions in one place.')
            ->assertSeeText('Upcoming transport days')
            ->assertDontSeeText('Working week')
            ->assertDontSeeText('Needs planning')
            ->assertSeeText('North Devon Veterinary Referral and Rehabilitation Centre')
            ->assertSeeText('2 horses')
            ->assertSeeText('Unassigned work')
            ->assertSeeText('No jobs assigned yet - available for new work.')
            ->assertDontSeeText('Optimise route')
            ->assertDontSeeText('Search jobs, customers, horses');

        $this->assertSame(1, substr_count($response->getContent(), '<h1>Dashboard</h1>'));
    }

    public function test_the_dashboard_header_uses_a_time_neutral_fallback_without_a_name(): void
    {
        $this->actingAs(User::factory()->create(['name' => '']));

        $this->get('/prototype/dashboard')
            ->assertOk()
            ->assertSeeText('Dashboard')
            ->assertSeeText('Sunday 20 September 2026')
            ->assertDontSeeText("Today's transport plan")
            ->assertDontSeeText('Your transport schedule, quote follow-ups, and next useful actions in one place.')
            ->assertDontSeeText('Welcome back,');
    }

    public function test_empty_fixture_unassigned_work_renders_only_the_compact_heading(): void
    {
        $this->actingAs(User::factory()->create());
        $dashboard = DashboardPrototypeFixture::make();
        $dashboard['unassigned_jobs'] = [];

        $content = view('dashboard-prototype.index', [
            'dashboard' => $dashboard,
            'greetingName' => null,
        ])->render();

        $this->assertStringContainsString('<h2>Unassigned work</h2>', $content);
        $this->assertStringContainsString('<span class="dashboard-count">0</span>', $content);
        $this->assertStringNotContainsString('class="unassigned-list"', $content);
    }

    public function test_summary_metrics_state_their_operational_scope(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/prototype/dashboard')
            ->assertOk()
            ->assertSee('<p class="dashboard-summary-label">Today</p>', false)
            ->assertSeeText('1 day')
            ->assertSeeText('3 jobs scheduled today')
            ->assertSee('<p class="dashboard-summary-label">Awaiting response</p>', false)
            ->assertSeeText('2 quotes')
            ->assertSeeText('Pending now')
            ->assertSeeText('Booked ahead')
            ->assertSeeText('Next 7 days')
            ->assertSee('<p class="dashboard-summary-label">Completed</p>', false)
            ->assertSeeText('Last 7 days')
            ->assertSeeText('Open quoted value')
            ->assertSeeText('Pending and quoted jobs');
    }

    public function test_transport_jobs_render_in_their_operational_order(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/prototype/dashboard')
            ->assertOk()
            ->assertSeeTextInOrder([
                'North Devon Veterinary Referral and Rehabilitation Centre',
                'Moorland Equestrian',
                'Harriet Collins',
            ]);
    }

    public function test_transport_jobs_explain_the_journey_and_expose_a_row_action(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/prototype/dashboard')
            ->assertOk()
            ->assertSeeTextInOrder([
                'Collect 2 horses',
                'North Devon Veterinary Referral and Rehabilitation Centre',
                'Collection: EX31 4JB',
                'Drop-off: TA4 3TP',
                'Booked',
                '£620',
                'View job',
            ])
            ->assertSee('class="route-job-action"', false);
    }

    public function test_schedule_date_controls_show_the_fixture_view_scope(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/prototype/dashboard')
            ->assertOk()
            ->assertSee('aria-label="Schedule date controls"', false)
            ->assertSeeText('20 - 26 Sep 2026')
            ->assertSeeText('Today')
            ->assertSeeText('Day')
            ->assertSeeText('Week')
            ->assertSeeText('Month')
            ->assertSee('aria-pressed="true">Week</button>', false);
    }

    public function test_sidebar_separates_primary_work_from_administration(): void
    {
        $this->actingAs(User::factory()->create());

        $content = $this->get('/prototype/dashboard')
            ->assertOk()
            ->assertSeeText('Administration')
            ->getContent();

        $this->assertSame(1, substr_count($content, 'New quote'));
    }

    public function test_schedule_fixture_renders_supported_job_variations(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/prototype/dashboard')
            ->assertOk()
            ->assertSeeText('1 horse')
            ->assertSeeText('2 horses')
            ->assertSeeText('Booked')
            ->assertSeeText('Pending')
            ->assertSeeText('Quoted')
            ->assertSeeText('Draft');
    }

    public function test_missing_operational_data_renders_explicit_fallbacks(): void
    {
        $this->actingAs(User::factory()->create());

        $content = $this->get('/prototype/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Drop-off: not recorded', $content);
        $this->assertStringNotContainsString('Mileage unavailable', $content);
        $this->assertStringNotContainsString('Duration unavailable', $content);
        $this->assertStringContainsString('Not priced', $content);
        preg_match_all('/<details class="transport-day-card"[^>]*>.*?<\/details>/s', $content, $cards);
        $emptyDays = array_values(array_filter(
            $cards[0],
            fn (string $card): bool => str_contains($card, 'Tuesday 22 September 2026'),
        ));

        $this->assertCount(1, $emptyDays);
        $emptyDay = $emptyDays[0];
        $this->assertStringContainsString('class="transport-day-fact transport-day-fact--jobs"', $emptyDay);
        $this->assertStringContainsString('class="transport-day-fact transport-day-fact--horses"', $emptyDay);
        $this->assertStringNotContainsString('transport-day-fact--miles', $emptyDay);
        $this->assertStringNotContainsString('transport-day-fact--duration', $emptyDay);
    }

    public function test_transport_days_use_the_intended_initial_disclosure_states(): void
    {
        $this->actingAs(User::factory()->create());

        $content = $this->get('/prototype/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match_all('/<details class="transport-day-card"\s+open\s*>/', $content));
        $this->assertSame(2, preg_match_all('/<details class="transport-day-card"\s*>/', $content));
    }
}
