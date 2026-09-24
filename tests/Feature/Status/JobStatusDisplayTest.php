<?php

namespace Tests\Feature\Status;

use App\Models\Customer;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\TransportDay;
use App\Models\User;
use App\Services\Scheduling\TransportDayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobStatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_statuses_config_defines_a_label_and_meaning_for_every_status(): void
    {
        foreach (['draft', 'quoted', 'pending', 'booked', 'completed', 'lost'] as $status) {
            $config = config("job_statuses.{$status}");

            $this->assertIsArray($config, "Expected config for [{$status}].");
            $this->assertNotEmpty($config['label'] ?? null, "Expected a label for [{$status}].");
            $this->assertNotEmpty($config['meaning'] ?? null, "Expected a meaning for [{$status}].");
        }
    }

    public function test_dashboard_renders_a_status_badge_with_its_colour_and_meaning_tooltip(): void
    {
        $this->actingAs(User::factory()->create());
        $this->jobWithRevision('quoted', 'Dashboard customer');

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('status-badge--quoted', false);
        $response->assertSee('title="Quote has been issued externally."', false);
    }

    public function test_transport_day_row_uses_the_status_coloured_badge(): void
    {
        $this->actingAs(User::factory()->create());
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $job = $this->jobWithRevision('draft', 'Day customer');
        app(TransportDayManager::class)->addJob($day, $job);

        $this->get("/transport-days/{$day->id}")
            ->assertOk()
            ->assertSee('status-badge--draft', false);
    }

    public function test_job_screen_shows_the_prominent_status_badge_and_its_meaning(): void
    {
        $this->actingAs(User::factory()->create());
        $job = $this->jobWithRevision('draft', 'Job screen customer');
        $revision = $job->currentWorkingRevision;

        $this->get("/jobs/{$job->id}/revisions/{$revision->id}")
            ->assertOk()
            ->assertSee('status-badge--draft', false)
            ->assertSeeText('Work-in-progress quote, not yet issued.');
    }

    private function jobWithRevision(string $status, string $customerName): Job
    {
        $job = Job::query()->create([
            'customer_id' => Customer::query()->create(['name' => $customerName])->id,
            'status' => $status,
        ]);
        $revision = JobRevision::query()->create([
            'job_id' => $job->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
            'engine_total' => '100.00',
            'final_total' => '100.00',
        ]);
        $job->forceFill(['current_working_revision_id' => $revision->id])->save();

        return $job->refresh();
    }
}
