<?php

namespace Tests\Feature\Scheduling;

use App\Models\Customer;
use App\Models\Job;
use App\Models\TransportDay;
use App\Services\Scheduling\TransportDayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TransportDayManagerTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): TransportDayManager
    {
        return app(TransportDayManager::class);
    }

    private function job(string $status = 'draft'): Job
    {
        return Job::query()->create([
            'customer_id' => Customer::query()->create(['name' => 'Customer '.uniqid()])->id,
            'status' => $status,
        ]);
    }

    public function test_add_job_appends_at_the_end_of_the_day(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $first = $this->job();
        $second = $this->job();

        $this->manager()->addJob($day, $first);
        $this->manager()->addJob($day, $second);

        $this->assertSame(1, $first->fresh()->transport_day_sequence);
        $this->assertSame(2, $second->fresh()->transport_day_sequence);
        $this->assertSame([$first->id, $second->id], $day->jobs()->pluck('jobs.id')->all());
    }

    public function test_add_job_moves_a_job_that_was_in_another_day(): void
    {
        $monday = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $tuesday = TransportDay::query()->create(['run_date' => '2026-08-25']);
        $job = $this->job();

        $this->manager()->addJob($monday, $job);
        $this->manager()->addJob($tuesday, $job);

        $this->assertSame(0, $monday->jobs()->count());
        $this->assertSame($tuesday->id, $job->fresh()->transport_day_id);
        $this->assertSame(1, $job->fresh()->transport_day_sequence);
    }

    public function test_remove_job_returns_it_to_unassigned(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $job = $this->job();
        $this->manager()->addJob($day, $job);

        $this->manager()->removeJob($job);

        $this->assertNull($job->fresh()->transport_day_id);
        $this->assertNull($job->fresh()->transport_day_sequence);
    }

    public function test_move_job_up_swaps_with_the_previous_job(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $first = $this->job();
        $second = $this->job();
        $this->manager()->addJob($day, $first);
        $this->manager()->addJob($day, $second);

        $this->manager()->moveJobUp($second);

        $this->assertSame([$second->id, $first->id], $day->jobs()->pluck('jobs.id')->all());
    }

    public function test_move_job_down_swaps_with_the_next_job(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $first = $this->job();
        $second = $this->job();
        $this->manager()->addJob($day, $first);
        $this->manager()->addJob($day, $second);

        $this->manager()->moveJobDown($first);

        $this->assertSame([$second->id, $first->id], $day->jobs()->pluck('jobs.id')->all());
    }

    public function test_move_job_up_on_the_first_job_is_a_no_op(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $first = $this->job();
        $second = $this->job();
        $this->manager()->addJob($day, $first);
        $this->manager()->addJob($day, $second);

        $this->manager()->moveJobUp($first);

        $this->assertSame([$first->id, $second->id], $day->jobs()->pluck('jobs.id')->all());
    }

    public function test_a_lost_job_cannot_be_added_to_a_day(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24']);
        $lost = $this->job('lost');

        $this->expectException(InvalidArgumentException::class);

        $this->manager()->addJob($day, $lost);
    }
}
