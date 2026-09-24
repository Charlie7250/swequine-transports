<?php

namespace Tests\Feature\Scheduling;

use App\Models\Customer;
use App\Models\Job;
use App\Models\TransportDay;
use App\Models\User;
use App\Services\Scheduling\TransportDayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportDayControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function job(string $status = 'draft', string $customerName = 'Acme'): Job
    {
        return Job::query()->create([
            'customer_id' => Customer::query()->create(['name' => $customerName])->id,
            'status' => $status,
        ]);
    }

    public function test_staff_can_create_a_transport_day(): void
    {
        $this->post('/transport-days', [
            'run_date' => '2026-08-24',
            'name' => 'Monday run',
        ])->assertRedirect();

        $this->assertDatabaseHas('transport_days', [
            'run_date' => '2026-08-24 00:00:00',
            'name' => 'Monday run',
        ]);
    }

    public function test_show_lists_member_jobs_in_order_and_an_add_job_picker(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $manager = app(TransportDayManager::class);
        $first = $this->job('draft', 'First customer');
        $second = $this->job('draft', 'Second customer');
        $manager->addJob($day, $first);
        $manager->addJob($day, $second);
        $unassigned = $this->job('draft', 'Unassigned customer');

        $this->get("/transport-days/{$day->id}")
            ->assertOk()
            ->assertSeeTextInOrder(['First customer', 'Second customer'])
            ->assertSeeText('Unassigned customer');
    }

    public function test_staff_can_add_a_job_to_a_day(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $job = $this->job();

        $this->post("/transport-days/{$day->id}/jobs", ['job_id' => $job->id])
            ->assertRedirect("/transport-days/{$day->id}");

        $this->assertSame($day->id, $job->fresh()->transport_day_id);
    }

    public function test_staff_cannot_add_a_lost_job_to_a_day(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $lost = $this->job('lost');

        $this->from("/transport-days/{$day->id}")
            ->post("/transport-days/{$day->id}/jobs", ['job_id' => $lost->id])
            ->assertRedirect("/transport-days/{$day->id}")
            ->assertSessionHasErrors('job_id');

        $this->assertNull($lost->fresh()->transport_day_id);
    }

    public function test_staff_can_remove_a_job_from_a_day(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $job = $this->job();
        app(TransportDayManager::class)->addJob($day, $job);

        $this->post("/transport-days/{$day->id}/jobs/{$job->id}/remove")
            ->assertRedirect("/transport-days/{$day->id}");

        $this->assertNull($job->fresh()->transport_day_id);
    }

    public function test_staff_can_move_a_job_up(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $manager = app(TransportDayManager::class);
        $first = $this->job('draft', 'First customer');
        $second = $this->job('draft', 'Second customer');
        $manager->addJob($day, $first);
        $manager->addJob($day, $second);

        $this->post("/transport-days/{$day->id}/jobs/{$second->id}/move-up")
            ->assertRedirect("/transport-days/{$day->id}");

        $this->assertSame([$second->id, $first->id], $day->jobs()->pluck('jobs.id')->all());
    }

    public function test_action_endpoints_reject_a_job_that_is_not_a_member_of_the_day(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $outsider = $this->job();

        $this->post("/transport-days/{$day->id}/jobs/{$outsider->id}/remove")
            ->assertNotFound();
    }
}
