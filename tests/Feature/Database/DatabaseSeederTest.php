<?php

namespace Tests\Feature\Database;

use App\Models\RateSetting;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_database_seeder_creates_a_local_staff_user_and_active_pricing_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'ops@sweq.local',
        ]);

        $this->assertSame(1, User::query()->count());
        $this->assertTrue(WeeklyFuelPrice::query()->sole()->is_active);
        $rateSetting = RateSetting::query()->sole();
        $this->assertTrue($rateSetting->is_active);
        $this->assertSame('1.500000', $rateSetting->one_horse_multiplier);
        $this->assertSame('1.750000', $rateSetting->two_horse_multiplier);
    }
}
