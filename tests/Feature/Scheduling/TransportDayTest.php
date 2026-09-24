<?php

namespace Tests\Feature\Scheduling;

use App\Models\Customer;
use App\Models\Job;
use App\Models\TransportDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransportDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_transport_day_schema_exists(): void
    {
        $this->assertTrue(Schema::hasTable('transport_days'));
        $this->assertTrue(Schema::hasColumns('jobs', ['transport_day_id', 'transport_day_sequence']));
    }

    public function test_transport_day_returns_its_jobs_in_sequence_order(): void
    {
        $day = TransportDay::query()->create(['run_date' => '2026-08-24', 'name' => 'Monday run']);
        $customer = Customer::query()->create(['name' => 'Day customer']);

        $second = Job::query()->create([
            'customer_id' => $customer->id,
            'status' => 'draft',
            'transport_day_id' => $day->id,
            'transport_day_sequence' => 2,
        ]);
        $first = Job::query()->create([
            'customer_id' => $customer->id,
            'status' => 'draft',
            'transport_day_id' => $day->id,
            'transport_day_sequence' => 1,
        ]);

        $this->assertSame(
            [$first->id, $second->id],
            $day->jobs()->pluck('jobs.id')->all(),
        );
    }
}
