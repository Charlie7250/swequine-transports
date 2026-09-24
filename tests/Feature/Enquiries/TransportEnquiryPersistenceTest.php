<?php

namespace Tests\Feature\Enquiries;

use App\Models\Customer;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\LoadingPracticeQuote;
use App\Models\RouteResolution;
use App\Models\RouteResolutionLeg;
use App\Models\TransportEnquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportEnquiryPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transport_enquiry_model_is_available(): void
    {
        $this->assertTrue(class_exists(TransportEnquiry::class));
    }

    public function test_an_incomplete_transport_enquiry_can_be_saved_as_a_draft(): void
    {
        $enquiry = TransportEnquiry::query()->create([
            'source' => 'private_enquiry',
        ]);

        $this->assertDatabaseHas('transport_enquiries', [
            'id' => $enquiry->id,
            'source' => 'private_enquiry',
            'status' => 'draft',
            'date_to_be_arranged' => 0,
            'special_constraints_acknowledged' => 0,
        ]);
        $enquiry->refresh();
        $this->assertNull($enquiry->customer_name);
        $this->assertSame('draft', $enquiry->status);
        $this->assertFalse($enquiry->date_to_be_arranged);
        $this->assertFalse($enquiry->special_constraints_acknowledged);
    }

    public function test_route_resolution_models_are_available(): void
    {
        $this->assertTrue(class_exists(RouteResolution::class));
        $this->assertTrue(class_exists(RouteResolutionLeg::class));
    }

    public function test_a_three_leg_route_resolution_audit_is_retrieved_in_sequence(): void
    {
        $enquiry = TransportEnquiry::query()->create([
            'source' => 'private_enquiry',
        ]);

        $resolution = RouteResolution::query()->create([
            'transport_enquiry_id' => $enquiry->id,
            'provider' => 'routing_provider',
            'provider_product' => 'driving_routes',
            'request_id' => 'request-123',
            'route_profile' => 'horsebox',
            'overall_status' => 'resolved',
            'operator_action_required' => true,
            'pricing_eligible' => true,
            'raw_input_snapshot' => [
                'legs' => [
                    ['origin' => 'EX1 1AA', 'destination' => 'EX2 2BB'],
                ],
            ],
            'normalisation_metadata' => ['postcode_source' => 'customer_input'],
            'provider_metadata' => ['request_region' => 'gb'],
            'warnings' => ['One waypoint was approximate'],
            'attempted_at' => '2026-08-21 10:00:00',
            'resolved_at' => '2026-08-21 10:00:04',
        ]);

        foreach ([
            ['sequence' => 1, 'leg_type' => 'depot_to_pickup', 'origin_input' => 'EX16 0AA', 'destination_input' => 'EX1 1AA'],
            ['sequence' => 2, 'leg_type' => 'pickup_to_drop_off', 'origin_input' => 'EX1 1AA', 'destination_input' => 'EX2 2BB'],
            ['sequence' => 3, 'leg_type' => 'drop_off_to_depot', 'origin_input' => 'EX2 2BB', 'destination_input' => 'EX16 0AA'],
        ] as $leg) {
            $resolution->legs()->create(array_merge($leg, [
                'status' => 'resolved',
                'distance_metres' => 16000 + ($leg['sequence'] * 1000),
                'quoted_miles' => 11 + $leg['sequence'],
                'duration_seconds' => 1200 + ($leg['sequence'] * 60),
                'provider_route_id' => 'route-'.$leg['sequence'],
                'resolved_origin_metadata' => ['postcode' => $leg['origin_input']],
                'resolved_destination_metadata' => ['postcode' => $leg['destination_input']],
            ]));
        }

        $resolution->refresh()->load('legs');

        $this->assertSame($enquiry->id, $resolution->transportEnquiry->id);
        $this->assertSame(['postcode_source' => 'customer_input'], $resolution->normalisation_metadata);
        $this->assertTrue($resolution->operator_action_required);
        $this->assertTrue($resolution->pricing_eligible);
        $this->assertSame([1, 2, 3], $resolution->legs->pluck('sequence')->all());
        $this->assertSame(['depot_to_pickup', 'pickup_to_drop_off', 'drop_off_to_depot'], $resolution->legs->pluck('leg_type')->all());
        $this->assertSame('route-2', $resolution->legs[1]->provider_route_id);
        $this->assertSame(['postcode' => 'EX2 2BB'], $resolution->legs[1]->resolved_destination_metadata);
    }

    public function test_quote_models_expose_the_transport_enquiry_and_route_resolution_links(): void
    {
        $this->assertTrue(method_exists(Job::class, 'transportEnquiries'));
        $this->assertTrue(method_exists(JobRevision::class, 'routeResolution'));

        $customer = Customer::query()->create(['name' => 'Amber Vale Eventing']);
        $job = Job::query()->create(['customer_id' => $customer->id]);
        $enquiry = TransportEnquiry::query()->create([
            'source' => 'private_enquiry',
            'quote_job_id' => $job->id,
        ]);
        $resolution = RouteResolution::query()->create([
            'transport_enquiry_id' => $enquiry->id,
            'overall_status' => 'resolved',
        ]);
        $revision = JobRevision::query()->create([
            'job_id' => $job->id,
            'revision_number' => 1,
            'route_resolution_id' => $resolution->id,
        ]);

        $this->assertSame($enquiry->id, $job->refresh()->transportEnquiries->sole()->id);
        $this->assertSame($resolution->id, $revision->routeResolution->id);
    }

    public function test_loading_practice_quotes_remain_unrelated_to_transport_enquiries(): void
    {
        $customer = Customer::query()->create(['name' => 'Amber Vale Eventing']);
        $loadingPracticeQuote = LoadingPracticeQuote::query()->create([
            'customer_id' => $customer->id,
        ]);
        $enquiry = TransportEnquiry::query()->create([
            'source' => 'private_enquiry',
            'customer_name' => 'Harriet Vale',
        ]);

        $this->assertSame($customer->id, $loadingPracticeQuote->customer_id);
        $this->assertNull($loadingPracticeQuote->getAttribute('transport_enquiry_id'));
        $this->assertNull($enquiry->quote_job_id);
        $this->assertDatabaseCount('loading_practice_quotes', 1);
        $this->assertDatabaseCount('transport_enquiries', 1);
    }
}
