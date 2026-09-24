<?php

namespace Tests\Feature\Quotes;

use App\Models\Customer;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\RateSetting;
use App\Models\RouteLeg;
use App\Models\SharedRun;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use App\Services\Pricing\JobRevisionPricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedRunBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_a_shared_run_and_attach_multiple_customer_allocations(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);

        $firstRevision = $this->createRevision([
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
        ], [
            'name' => 'Amber Vale Eventing',
        ]);
        $this->createStandardRouteLegs($firstRevision, [10, 90, 96]);

        $secondRevision = $this->createRevision([
            'pickup_postcode' => 'EX2 8BB',
            'dropoff_postcode' => 'TA2 7QQ',
        ], [
            'name' => 'Moorland Dressage',
        ]);
        $this->createStandardRouteLegs($secondRevision, [14, 75, 80]);

        $response = $this->post('/shared-runs', [
            'name' => 'Thursday Mid Devon shared run',
            'run_date' => '2026-08-20',
            'notes' => 'Two customers sharing the loaded middle section only where their route overlaps.',
            'allocations' => [
                [
                    'job_revision_id' => $firstRevision->id,
                    'legs' => [
                        [
                            'full_miles' => 6,
                            'split_miles' => 4,
                            'split_divisor' => 2,
                            'reason' => 'First customer covers the solo approach, then shares the final approach into the collection point.',
                        ],
                        [
                            'full_miles' => 60,
                            'split_miles' => 30,
                            'split_divisor' => 2,
                            'reason' => 'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.',
                        ],
                        [
                            'full_miles' => 96,
                            'split_miles' => 0,
                            'split_divisor' => null,
                            'reason' => 'The return to depot is this customer’s own section after the shared route ends.',
                        ],
                    ],
                ],
                [
                    'job_revision_id' => $secondRevision->id,
                    'legs' => [
                        [
                            'full_miles' => 4,
                            'split_miles' => 4,
                            'split_divisor' => 2,
                            'reason' => 'This tagged-on client joins near the pickup side, so only the final approach is shared.',
                        ],
                        [
                            'full_miles' => 45,
                            'split_miles' => 30,
                            'split_divisor' => 2,
                            'reason' => 'This customer still pays their own first loaded section, then shares the overlapped segment.',
                        ],
                        [
                            'full_miles' => 80,
                            'split_miles' => 0,
                            'split_divisor' => null,
                            'reason' => 'This customer covers their own unloaded return once the genuinely shared section ends.',
                        ],
                    ],
                ],
            ],
        ]);

        $sharedRun = SharedRun::query()->with('allocations.jobRevision.job.customer')->firstOrFail();
        $firstRevision->refresh();
        $secondRevision->refresh();

        $response->assertRedirect("/shared-runs/{$sharedRun->id}");
        $this->assertSame('Thursday Mid Devon shared run', $sharedRun->name);
        $this->assertCount(2, $sharedRun->allocations);
        $this->assertSame('240.87', $firstRevision->engine_total);
        $this->assertSame('197.91', $secondRevision->engine_total);
        $this->assertSame('34', (string) $sharedRun->allocations[0]->split_charge_miles);
        $this->assertSame('240.87', $sharedRun->allocations[0]->total_charge);
        $this->assertSame('shared_run', $firstRevision->calculation_explanation['shared_load']['type']);
        $this->assertSame('0.750000', $firstRevision->calculation_explanation['shared_load']['shared_load_percentage']);
        $this->assertSame('The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.', $firstRevision->calculation_explanation['shared_load']['allocation_legs'][1]['reason']);

        $this->get("/jobs/{$firstRevision->job_id}/revisions/{$firstRevision->id}")
            ->assertOk()
            ->assertSeeText('Shared allocation reasoning')
            ->assertSeeText('Thursday Mid Devon shared run')
            ->assertSeeText('75.00% of the loaded rate')
            ->assertSeeText('First customer covers the solo approach, then shares the final approach into the collection point.');
    }

    public function test_shared_run_builder_redisplays_the_form_after_a_validation_error(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);

        $firstRevision = $this->createRevision([
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
        ], [
            'name' => 'Amber Vale Eventing',
        ]);
        $this->createStandardRouteLegs($firstRevision, [10, 90, 96]);

        $secondRevision = $this->createRevision([
            'pickup_postcode' => 'EX2 8BB',
            'dropoff_postcode' => 'TA2 7QQ',
        ], [
            'name' => 'Moorland Dressage',
        ]);
        $this->createStandardRouteLegs($secondRevision, [14, 75, 80]);

        $response = $this->from('/shared-runs/create')
            ->followingRedirects()
            ->post('/shared-runs', [
                'name' => 'Thursday Mid Devon shared run',
                'allocations' => [
                    [
                        'job_revision_id' => $firstRevision->id,
                        'legs' => [
                            [
                                'full_miles' => 6,
                                'split_miles' => 4,
                                'split_divisor' => '',
                                'reason' => 'First customer covers the solo approach, then shares the final approach into the collection point.',
                            ],
                            [
                                'full_miles' => 60,
                                'split_miles' => 30,
                                'split_divisor' => 2,
                                'reason' => 'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.',
                            ],
                            [
                                'full_miles' => 96,
                                'split_miles' => 0,
                                'split_divisor' => '',
                                'reason' => 'The return to depot is this customer’s own section after the shared route ends.',
                            ],
                        ],
                    ],
                    [
                        'job_revision_id' => $secondRevision->id,
                        'legs' => [
                            [
                                'full_miles' => 4,
                                'split_miles' => 4,
                                'split_divisor' => 2,
                                'reason' => 'This tagged-on client joins near the pickup side, so only the final approach is shared.',
                            ],
                            [
                                'full_miles' => 45,
                                'split_miles' => 30,
                                'split_divisor' => 2,
                                'reason' => 'This customer still pays their own first loaded section, then shares the overlapped segment.',
                            ],
                            [
                                'full_miles' => 80,
                                'split_miles' => 0,
                                'split_divisor' => null,
                                'reason' => 'This customer covers their own unloaded return once the genuinely shared section ends.',
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertSeeText('Split mileage requires a split divisor.');
        $response->assertSee('value="Thursday Mid Devon shared run"', false);
        $this->assertDatabaseCount('shared_runs', 0);
    }

    public function test_shared_run_builder_explains_when_no_draft_revisions_are_ready(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/shared-runs/create');

        $response->assertOk();
        $response->assertSeeText('No draft transport quote revisions are ready for shared-load planning yet.');
        $response->assertSeeText('Create a new transport quote, or return an existing quote to draft before building a shared run.');
        $response->assertDontSeeText('Save shared run');
    }

    public function test_updating_a_shared_run_reprices_a_detached_revision_as_a_standard_quote(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);

        $firstRevision = $this->createRevision([
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
        ], [
            'name' => 'Amber Vale Eventing',
        ]);
        $this->createStandardRouteLegs($firstRevision, [10, 90, 96]);

        $secondRevision = $this->createRevision([
            'pickup_postcode' => 'EX2 8BB',
            'dropoff_postcode' => 'TA2 7QQ',
        ], [
            'name' => 'Moorland Dressage',
        ]);
        $this->createStandardRouteLegs($secondRevision, [14, 75, 80]);

        $this->post('/shared-runs', [
            'name' => 'Thursday Mid Devon shared run',
            'run_date' => '2026-08-20',
            'allocations' => [
                [
                    'job_revision_id' => $firstRevision->id,
                    'legs' => [
                        ['full_miles' => 6, 'split_miles' => 4, 'split_divisor' => 2, 'reason' => 'First customer covers the solo approach, then shares the final approach into the collection point.'],
                        ['full_miles' => 60, 'split_miles' => 30, 'split_divisor' => 2, 'reason' => 'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.'],
                        ['full_miles' => 96, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'The return to depot is this customer’s own section after the shared route ends.'],
                    ],
                ],
                [
                    'job_revision_id' => $secondRevision->id,
                    'legs' => [
                        ['full_miles' => 4, 'split_miles' => 4, 'split_divisor' => 2, 'reason' => 'This tagged-on client joins near the pickup side, so only the final approach is shared.'],
                        ['full_miles' => 45, 'split_miles' => 30, 'split_divisor' => 2, 'reason' => 'This customer still pays their own first loaded section, then shares the overlapped segment.'],
                        ['full_miles' => 80, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'This customer covers their own unloaded return once the genuinely shared section ends.'],
                    ],
                ],
            ],
        ])->assertRedirect();

        $sharedRun = SharedRun::query()->firstOrFail();

        $this->patch("/shared-runs/{$sharedRun->id}", [
            'name' => 'Thursday Mid Devon shared run',
            'run_date' => '2026-08-20',
            'allocations' => [
                [
                    'job_revision_id' => $firstRevision->id,
                    'legs' => [
                        ['full_miles' => 10, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'Once the second customer is removed, the full approach belongs to this customer again.'],
                        ['full_miles' => 90, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'Once the second customer is removed, the full loaded section belongs to this customer again.'],
                        ['full_miles' => 96, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'The return to depot remains this customer’s own section.'],
                    ],
                ],
            ],
        ])->assertRedirect("/shared-runs/{$sharedRun->id}");

        $firstRevision->refresh();
        $secondRevision->refresh();

        $this->assertDatabaseCount('shared_run_allocations', 1);
        $this->assertSame('255.90', $firstRevision->engine_total);
        $this->assertSame('218.47', $secondRevision->engine_total);
        $this->assertArrayNotHasKey('shared_load', $secondRevision->calculation_explanation);
    }

    public function test_updating_a_shared_load_quote_preserves_the_previous_revision_allocation_snapshot(): void
    {
        $this->withExceptionHandling();
        $this->actingAs(User::factory()->create());

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);

        $firstRevision = $this->createRevision([
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
        ], [
            'name' => 'Amber Vale Eventing',
        ]);
        $this->createStandardRouteLegs($firstRevision, [10, 90, 96]);

        $secondRevision = $this->createRevision([
            'pickup_postcode' => 'EX2 8BB',
            'dropoff_postcode' => 'TA2 7QQ',
        ], [
            'name' => 'Moorland Dressage',
        ]);
        $this->createStandardRouteLegs($secondRevision, [14, 75, 80]);

        $this->post('/shared-runs', [
            'name' => 'Thursday Mid Devon shared run',
            'run_date' => '2026-08-20',
            'allocations' => [
                [
                    'job_revision_id' => $firstRevision->id,
                    'legs' => [
                        ['full_miles' => 6, 'split_miles' => 4, 'split_divisor' => 2, 'reason' => 'First customer covers the solo approach, then shares the final approach into the collection point.'],
                        ['full_miles' => 60, 'split_miles' => 30, 'split_divisor' => 2, 'reason' => 'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.'],
                        ['full_miles' => 96, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'The return to depot is this customer’s own section after the shared route ends.'],
                    ],
                ],
                [
                    'job_revision_id' => $secondRevision->id,
                    'legs' => [
                        ['full_miles' => 4, 'split_miles' => 4, 'split_divisor' => 2, 'reason' => 'This tagged-on client joins near the pickup side, so only the final approach is shared.'],
                        ['full_miles' => 45, 'split_miles' => 30, 'split_divisor' => 2, 'reason' => 'This customer still pays their own first loaded section, then shares the overlapped segment.'],
                        ['full_miles' => 80, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'This customer covers their own unloaded return once the genuinely shared section ends.'],
                    ],
                ],
            ],
        ])->assertRedirect();

        $sharedRun = SharedRun::query()->firstOrFail();

        $response = $this->patch("/jobs/{$firstRevision->job_id}/revisions/{$firstRevision->id}", [
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
                ['miles' => 95],
                ['miles' => 100],
            ],
        ]);

        $updatedRevision = JobRevision::query()
            ->where('job_id', $firstRevision->job_id)
            ->orderByDesc('revision_number')
            ->firstOrFail();

        $response->assertRedirect("/jobs/{$firstRevision->job_id}/revisions/{$updatedRevision->id}");
        $this->assertDatabaseCount('shared_run_allocations', 3);
        $this->assertDatabaseHas('shared_run_allocations', [
            'shared_run_id' => $sharedRun->id,
            'job_revision_id' => $firstRevision->id,
        ]);
        $this->assertDatabaseHas('shared_run_allocations', [
            'shared_run_id' => $sharedRun->id,
            'job_revision_id' => $updatedRevision->id,
        ]);
        $this->assertSame(162, $firstRevision->sharedRunAllocation()->firstOrFail()->full_charge_miles);
        $this->assertSame(34, $firstRevision->sharedRunAllocation()->firstOrFail()->split_charge_miles);
        $this->assertSame(
            'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.',
            $firstRevision->sharedRunAllocation()->firstOrFail()->allocation_explanation['allocation_legs'][1]['reason'],
        );

        $this->get("/jobs/{$firstRevision->job_id}/revisions/{$firstRevision->id}")
            ->assertOk()
            ->assertSeeText('Thursday Mid Devon shared run')
            ->assertSeeText('Shared allocation reasoning');

        $this->get("/shared-runs/{$sharedRun->id}")
            ->assertOk()
            ->assertSeeText('Customer allocation 2')
            ->assertDontSeeText('Customer allocation 3');
    }

    public function test_shared_allocation_repricing_preserves_an_overridden_revision(): void
    {
        $actor = User::factory()->create(['can_manage_quote_exceptions' => true]);
        $this->actingAs($actor);
        $this->createWeeklyFuelPrice(['is_active' => true, 'activated_at' => now()]);
        $this->createRateSetting(['is_active' => true]);

        $firstRevision = $this->createRevision([], ['name' => 'Amber Vale Eventing']);
        $this->createStandardRouteLegs($firstRevision, [10, 90, 96]);
        $secondRevision = $this->createRevision([
            'pickup_postcode' => 'EX2 8BB',
            'dropoff_postcode' => 'TA2 7QQ',
        ], ['name' => 'Moorland Dressage']);
        $this->createStandardRouteLegs($secondRevision, [14, 75, 80]);
        app(JobRevisionPricingEngine::class)->price($firstRevision);

        $this->post("/jobs/{$firstRevision->job_id}/revisions/{$firstRevision->id}/exceptions/final-total", [
            'final_total' => '250.00',
            'reason_category' => 'commercial_adjustment',
            'explanation' => 'Approved rounded customer total',
        ])->assertRedirect();

        $overriddenRevision = $firstRevision->job->fresh()->currentWorkingRevision;
        $this->post('/shared-runs', $this->sharedRunPayload($overriddenRevision->id, $secondRevision->id))
            ->assertRedirect();

        $currentRevision = $firstRevision->job->fresh()->currentWorkingRevision;

        $this->assertNotSame($overriddenRevision->id, $currentRevision->id);
        $this->assertSame('250.00', $overriddenRevision->fresh()->final_total);
        $this->assertTrue($overriddenRevision->quoteExceptionAudits()->where('exception_type', 'final_total_override')->exists());
        $this->assertNull($overriddenRevision->sharedRunAllocation()->first());
        $this->assertSame('240.87', $currentRevision->engine_total);
        $this->assertSame('240.87', $currentRevision->final_total);
        $this->assertNull($currentRevision->manual_final_total_reason);
        $this->assertFalse($currentRevision->quoteExceptionAudits()->where('exception_type', 'final_total_override')->exists());
        $this->assertNotNull($currentRevision->sharedRunAllocation()->first());
    }

    public function test_shared_run_builder_rejects_fractional_allocation_miles(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);

        $firstRevision = $this->createRevision([], ['name' => 'Amber Vale Eventing']);
        $this->createStandardRouteLegs($firstRevision, [10, 90, 96]);

        $secondRevision = $this->createRevision([
            'pickup_postcode' => 'EX2 8BB',
            'dropoff_postcode' => 'TA2 7QQ',
        ], ['name' => 'Moorland Dressage']);
        $this->createStandardRouteLegs($secondRevision, [14, 75, 80]);

        $this->from('/shared-runs/create')
            ->post('/shared-runs', [
                'name' => 'Thursday Mid Devon shared run',
                'allocations' => [
                    [
                        'job_revision_id' => $firstRevision->id,
                        'legs' => [
                            ['full_miles' => '5.5', 'split_miles' => 4, 'split_divisor' => 2, 'reason' => 'First customer covers the solo approach, then shares the final approach into the collection point.'],
                            ['full_miles' => 60, 'split_miles' => 30, 'split_divisor' => 2, 'reason' => 'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.'],
                            ['full_miles' => 96, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'The return to depot is this customer’s own section after the shared route ends.'],
                        ],
                    ],
                    [
                        'job_revision_id' => $secondRevision->id,
                        'legs' => [
                            ['full_miles' => 4, 'split_miles' => 4, 'split_divisor' => 2, 'reason' => 'This tagged-on client joins near the pickup side, so only the final approach is shared.'],
                            ['full_miles' => 45, 'split_miles' => 30, 'split_divisor' => 2, 'reason' => 'This customer still pays their own first loaded section, then shares the overlapped segment.'],
                            ['full_miles' => 80, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'This customer covers their own unloaded return once the genuinely shared section ends.'],
                        ],
                    ],
                ],
            ])
            ->assertRedirect('/shared-runs/create')
            ->assertSessionHasErrors('allocations.0.legs.0.full_miles');

        $this->assertDatabaseCount('shared_runs', 0);
    }

    public function test_shared_run_builder_rejects_inconsistent_split_divisors_on_the_same_leg(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createWeeklyFuelPrice([
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);

        $firstRevision = $this->createRevision([], ['name' => 'Amber Vale Eventing']);
        $this->createStandardRouteLegs($firstRevision, [10, 90, 96]);

        $secondRevision = $this->createRevision([
            'pickup_postcode' => 'EX2 8BB',
            'dropoff_postcode' => 'TA2 7QQ',
        ], ['name' => 'Moorland Dressage']);
        $this->createStandardRouteLegs($secondRevision, [14, 75, 80]);

        $this->from('/shared-runs/create')
            ->post('/shared-runs', [
                'name' => 'Thursday Mid Devon shared run',
                'allocations' => [
                    [
                        'job_revision_id' => $firstRevision->id,
                        'legs' => [
                            ['full_miles' => 6, 'split_miles' => 4, 'split_divisor' => 2, 'reason' => 'First customer covers the solo approach, then shares the final approach into the collection point.'],
                            ['full_miles' => 60, 'split_miles' => 30, 'split_divisor' => 2, 'reason' => 'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.'],
                            ['full_miles' => 96, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'The return to depot is this customer’s own section after the shared route ends.'],
                        ],
                    ],
                    [
                        'job_revision_id' => $secondRevision->id,
                        'legs' => [
                            ['full_miles' => 4, 'split_miles' => 4, 'split_divisor' => 3, 'reason' => 'This tagged-on client joins near the pickup side, so only the final approach is shared.'],
                            ['full_miles' => 45, 'split_miles' => 30, 'split_divisor' => 2, 'reason' => 'This customer still pays their own first loaded section, then shares the overlapped segment.'],
                            ['full_miles' => 80, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'This customer covers their own unloaded return once the genuinely shared section ends.'],
                        ],
                    ],
                ],
            ])
            ->assertRedirect('/shared-runs/create')
            ->assertSessionHasErrors('allocations.1.legs.0.split_divisor');

        $this->assertDatabaseCount('shared_runs', 0);
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

    private function sharedRunPayload(int $firstRevisionId, int $secondRevisionId): array
    {
        return [
            'name' => 'Thursday Mid Devon shared run',
            'run_date' => '2026-08-20',
            'allocations' => [
                [
                    'job_revision_id' => $firstRevisionId,
                    'legs' => [
                        ['full_miles' => 6, 'split_miles' => 4, 'split_divisor' => 2, 'reason' => 'The final approach is shared.'],
                        ['full_miles' => 60, 'split_miles' => 30, 'split_divisor' => 2, 'reason' => 'The final loaded section is shared.'],
                        ['full_miles' => 96, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'The return is not shared.'],
                    ],
                ],
                [
                    'job_revision_id' => $secondRevisionId,
                    'legs' => [
                        ['full_miles' => 4, 'split_miles' => 4, 'split_divisor' => 2, 'reason' => 'The final approach is shared.'],
                        ['full_miles' => 45, 'split_miles' => 30, 'split_divisor' => 2, 'reason' => 'The final loaded section is shared.'],
                        ['full_miles' => 80, 'split_miles' => 0, 'split_divisor' => null, 'reason' => 'The return is not shared.'],
                    ],
                ],
            ],
        ];
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
            'shared_load_percentage' => '0.750000',
            'two_horse_multiplier' => '1.150000',
            'is_active' => false,
            'effective_from' => '2026-08-10',
        ], $overrides));
    }

    private function createRevision(array $revisionOverrides = [], array $customerOverrides = []): JobRevision
    {
        $customer = Customer::query()->create(array_merge([
            'name' => 'South West Equine Customer',
        ], $customerOverrides));

        $job = Job::query()->create([
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

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
}
