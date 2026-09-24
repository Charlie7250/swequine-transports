<?php

namespace App\Services\Quotes;

use App\Models\Customer;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\QuoteExceptionAudit;
use App\Models\RouteLeg;
use App\Models\RouteResolution;
use App\Models\RouteResolutionLeg;
use App\Models\RouteResolutionReviewDecision;
use App\Models\TransportEnquiry;
use App\Models\User;
use App\Services\Pricing\DeterministicPricingCalculator;
use App\Services\Pricing\JobRevisionPricingContextResolver;
use App\Services\Pricing\JobRevisionPricingEngine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class QuoteWorkspaceManager
{
    private const AUTOMATIC_ROUTE_LEG_DEFINITIONS = [
        'depot_to_pickup' => ['label' => 'depot_to_pickup', 'rate_type' => 'unloaded'],
        'pickup_to_drop_off' => ['label' => 'pickup_to_dropoff', 'rate_type' => 'loaded'],
        'drop_off_to_depot' => ['label' => 'dropoff_to_depot', 'rate_type' => 'unloaded'],
    ];

    private const ROUTE_EXCEPTION_STATUSES = [
        'resolved_with_warning',
        'operator_review_required',
        'invalid_input',
        'unresolved',
        'provider_unavailable',
        'provider_response_invalid',
    ];

    private const MANUAL_FALLBACK_STATUSES = [
        'operator_review_required',
        'unresolved',
        'provider_unavailable',
        'provider_response_invalid',
    ];

    public function __construct(
        private readonly IssuedQuoteChecklist $issuedQuoteChecklist,
        private readonly IssuedQuoteEvidenceBuilder $issuedQuoteEvidenceBuilder,
        private readonly JobRevisionPricingContextResolver $pricingContextResolver,
        private readonly JobRevisionPricingEngine $pricingEngine,
    ) {}

    public function create(array $attributes): JobRevision
    {
        return DB::transaction(function () use ($attributes): JobRevision {
            $customer = Customer::query()->create($this->customerAttributes($attributes));
            $job = Job::query()->create([
                'customer_id' => $customer->id,
                'status' => 'draft',
            ]);
            $revision = JobRevision::query()->create(array_merge(
                $this->revisionAttributes($attributes),
                [
                    'job_id' => $job->id,
                    'revision_number' => 1,
                ],
            ));

            $job->forceFill([
                'current_working_revision_id' => $revision->id,
            ])->save();

            $this->syncRouteLegs($revision, $attributes['route_legs']);

            return $this->priceRevision($revision);
        });
    }

    public function createFromAcceptedRoute(TransportEnquiry $enquiry, RouteResolution $resolution, ?int $actorId): JobRevision
    {
        return DB::transaction(function () use ($enquiry, $resolution, $actorId): JobRevision {
            $lockedEnquiry = $this->lockTransportEnquiry($enquiry);
            $lockedResolution = $this->lockRouteResolution($resolution);

            $this->ensureEligibleRouteResolution($lockedEnquiry, $lockedResolution);
            $this->recordReviewAcceptance($lockedResolution, $actorId);

            $existingRevision = $this->existingRouteFirstRevision($lockedEnquiry);
            if ($existingRevision !== null) {
                return $existingRevision;
            }

            $this->ensurePricingContextAvailable();
            $revision = $this->createRouteFirstRevision($lockedEnquiry, $lockedResolution);
            $pricedRevision = $this->priceRevision($revision);

            $lockedEnquiry->update([
                'status' => 'draft_quote',
                'quote_job_id' => $pricedRevision->job_id,
            ]);

            return $pricedRevision;
        });
    }

    public function createFromManualFallback(
        TransportEnquiry $enquiry,
        RouteResolution $resolution,
        array $attributes,
        int $actorId,
    ): JobRevision {
        return DB::transaction(function () use ($enquiry, $resolution, $attributes, $actorId): JobRevision {
            $lockedEnquiry = $this->lockTransportEnquiry($enquiry);
            $lockedResolution = $this->lockRouteResolution($resolution);

            $this->ensureExceptionActorAuthorized($actorId);
            $this->ensureManualFallbackAvailable($lockedEnquiry, $lockedResolution);
            $this->ensurePricingContextAvailable();

            if ($this->existingRouteFirstRevision($lockedEnquiry) !== null) {
                throw ValidationException::withMessages(['route' => 'This enquiry already has a draft quote. Use the draft exception controls instead.']);
            }

            $revision = $this->createRouteFirstRevisionWithoutRouteLegs($lockedEnquiry, $lockedResolution);
            $this->replaceAutomaticRouteLegsWithManualFallback($revision, $lockedResolution, $attributes, $actorId);
            $pricedRevision = $this->priceRevision($revision);

            $lockedEnquiry->update([
                'status' => 'draft_quote',
                'quote_job_id' => $pricedRevision->job_id,
            ]);

            return $pricedRevision;
        });
    }

    public function overrideRouteLegs(JobRevision $revision, array $attributes, int $actorId): JobRevision
    {
        return DB::transaction(function () use ($revision, $attributes, $actorId): JobRevision {
            $job = $this->lockJob($revision);

            $this->ensureExceptionActorAuthorized($actorId);
            $this->ensureStatus($job->status, ['draft'], 'Route legs can only be overridden while the quote is in draft.');
            $this->ensureCurrentWorkingRevision($job, $revision, 'Only the current working revision can be changed.');
            $this->ensureRouteLegOverridesAvailable($revision);

            $nextRevision = $this->duplicateRevision(
                $job,
                $revision,
                excludedExceptionTypes: ['final_total_override'],
            );
            $this->applyRouteLegOverrides($nextRevision, $attributes['overrides'], $actorId);
            $job->forceFill(['current_working_revision_id' => $nextRevision->id])->save();

            return $this->priceRevision($nextRevision);
        });
    }

    public function overrideFinalTotal(JobRevision $revision, array $attributes, int $actorId): JobRevision
    {
        return DB::transaction(function () use ($revision, $attributes, $actorId): JobRevision {
            $job = $this->lockJob($revision);

            $this->ensureExceptionActorAuthorized($actorId);
            $this->ensureStatus($job->status, ['draft'], 'The final total can only be overridden while the quote is in draft.');
            $this->ensureCurrentWorkingRevision($job, $revision, 'Only the current working revision can be changed.');

            $nextRevision = $this->duplicateRevision($job, $revision, [
                'final_total' => $attributes['final_total'],
                'manual_final_total_reason' => $attributes['explanation'],
            ]);
            $this->priceRevision($nextRevision, (string) $attributes['final_total']);
            $this->recordFinalTotalOverride($nextRevision, $attributes, $actorId);
            $job->forceFill(['current_working_revision_id' => $nextRevision->id])->save();

            return $nextRevision->fresh();
        });
    }

    public function selectFuelPrice(JobRevision $revision, int $weeklyFuelPriceId): JobRevision
    {
        return DB::transaction(function () use ($revision, $weeklyFuelPriceId): JobRevision {
            $job = $this->lockJob($revision);

            $this->ensureStatus($job->status, ['draft'], 'Fuel prices can only be changed while the quote is in draft.');
            $this->ensureCurrentWorkingRevision($job, $revision, 'Only the current working revision can use a corrected fuel price.');

            $nextRevision = $this->duplicateRevision(
                $job,
                $revision,
                [
                    'weekly_fuel_price_id' => $weeklyFuelPriceId,
                    'final_total' => null,
                    'manual_final_total_reason' => null,
                ],
                ['final_total_override'],
            );
            $job->forceFill(['current_working_revision_id' => $nextRevision->id])->save();

            return $this->recordFuelCorrectionSelection($this->priceRevision($nextRevision));
        });
    }

    public function reviseForSharedAllocationChange(
        JobRevision $revision,
        bool $copySharedRunAllocation = true,
    ): JobRevision {
        $job = $this->lockJob($revision);

        $this->ensureStatus($job->status, ['draft'], 'Shared allocations can only change while the quote is in draft.');
        $this->ensureCurrentWorkingRevision($job, $revision, 'Only the current working revision can change its shared allocation.');

        if (! $revision->quoteExceptionAudits()->where('exception_type', 'final_total_override')->exists()) {
            return $revision;
        }

        $nextRevision = $this->duplicateRevision(
            $job,
            $revision,
            ['final_total' => null, 'manual_final_total_reason' => null],
            ['final_total_override'],
            $copySharedRunAllocation,
        );
        $job->forceFill(['current_working_revision_id' => $nextRevision->id])->save();

        return $nextRevision;
    }

    public function update(JobRevision $revision, array $attributes, int $actorId): JobRevision
    {
        return DB::transaction(function () use ($revision, $attributes, $actorId): JobRevision {
            $job = $this->lockJob($revision);

            $this->ensureRouteFirstRevisionCannotBeUpdated($revision);
            $this->ensureCurrentWorkingRevision($job, $revision, 'Quote workspace can only be updated from the current working revision.');

            if (in_array($job->status, ['quoted', 'pending'], true)) {
                $job->forceFill(['status' => 'draft'])->save();
            } else {
                $this->ensureStatus($job->status, ['draft'], 'Quote workspace can only be updated while the quote is in draft.');
            }

            return $this->saveWorkspaceRevision($job, $revision, $attributes, $actorId);
        });
    }

    private function saveWorkspaceRevision(
        Job $job,
        JobRevision $revision,
        array $attributes,
        int $actorId,
    ): JobRevision {
        $job->customer()->update($this->customerAttributes($attributes));
        $nextRevision = $this->createWorkspaceRevision($job, $revision, $attributes);
        $job->forceFill(['current_working_revision_id' => $nextRevision->id])->save();
        $this->duplicateSharedRunAllocation($revision, $nextRevision);
        $this->syncRouteLegs($nextRevision, $attributes['route_legs']);

        $manualFinalTotal = $attributes['manual_final_total'] ?? null;
        $pricedRevision = $this->priceRevision($nextRevision, $manualFinalTotal);
        $this->recordWorkspaceFinalOverride($pricedRevision, $attributes, $actorId);

        return $pricedRevision->fresh();
    }

    private function createWorkspaceRevision(Job $job, JobRevision $revision, array $attributes): JobRevision
    {
        return JobRevision::query()->create(array_merge(
            $this->revisionAttributes($attributes),
            [
                'job_id' => $job->id,
                'revision_number' => $this->nextRevisionNumber($job),
                'weekly_fuel_price_id' => $revision->weekly_fuel_price_id,
                'rate_setting_id' => $revision->rate_setting_id,
                'depot_postcode_override' => $revision->depot_postcode_override,
            ],
        ));
    }

    private function recordWorkspaceFinalOverride(JobRevision $revision, array $attributes, int $actorId): void
    {
        if (($attributes['manual_final_total'] ?? null) === null) {
            return;
        }

        $this->ensureExceptionActorAuthorized($actorId);
        $this->recordFinalTotalOverride($revision, [
            'final_total' => $attributes['manual_final_total'],
            'reason_category' => $attributes['manual_final_total_reason_category'],
            'explanation' => $attributes['manual_final_total_reason'],
        ], $actorId);
    }

    public function issue(JobRevision $revision, int $actorId): void
    {
        DB::transaction(function () use ($revision, $actorId): void {
            $job = $this->lockJob($revision);

            $this->ensureStatus($job->status, ['draft'], 'Quote can only be issued from draft.');
            $this->ensureCurrentWorkingRevision($job, $revision, 'Only the current working revision can be issued.');
            $this->priceRevision($revision, $this->auditedFinalTotal($revision));
            $issuedRevision = $revision->fresh();
            $this->ensureIssueChecklistIsComplete($issuedRevision);
            $issuedAt = now();

            $issuedRevision->forceFill([
                'issued_at' => $issuedAt,
                'issued_by_user_id' => $actorId,
                'issue_reference' => $this->issueReference($issuedRevision),
                'issued_evidence' => $this->issuedQuoteEvidenceBuilder->build($issuedRevision, $issuedAt),
            ])->save();

            $revision->job()->update([
                'status' => 'quoted',
                'current_working_revision_id' => $revision->id,
                'issued_revision_id' => $revision->id,
                'issued_at' => $issuedAt,
            ]);
        });
    }

    public function markPending(JobRevision $revision): void
    {
        DB::transaction(function () use ($revision): void {
            $job = $this->lockJob($revision);

            $this->ensureStatus($job->status, ['quoted'], 'Quote can only be marked as pending from quoted.');
            $this->ensureCurrentWorkingRevision($job, $revision, 'Only the current working revision can move into pending.');

            $job->forceFill([
                'status' => 'pending',
                'current_working_revision_id' => $revision->id,
            ])->save();
        });
    }

    public function returnToDraft(JobRevision $revision): JobRevision
    {
        return DB::transaction(function () use ($revision): JobRevision {
            $job = $this->lockJob($revision);

            $this->ensureStatus($job->status, ['quoted', 'pending'], 'Quote can only return to draft from quoted or pending.');
            $this->ensureCurrentWorkingRevision($job, $revision, 'Only the current working revision can return to draft.');
            $draftRevision = $this->duplicateRevision($job, $revision);

            $job->forceFill([
                'status' => 'draft',
                'current_working_revision_id' => $draftRevision->id,
            ])->save();

            return $draftRevision;
        });
    }

    public function book(JobRevision $revision): void
    {
        DB::transaction(function () use ($revision): void {
            $job = $this->lockJob($revision);

            $this->ensureStatus($job->status, ['quoted', 'pending'], 'Quote can only be marked as booked from quoted or pending.');
            $this->ensureCurrentWorkingRevision($job, $revision, 'Only the current working revision can be booked.');

            $job->forceFill([
                'status' => 'booked',
                'current_working_revision_id' => $revision->id,
                'accepted_revision_id' => $revision->id,
                'booked_at' => now(),
            ])->save();
        });
    }

    public function complete(JobRevision $revision): void
    {
        DB::transaction(function () use ($revision): void {
            $job = $this->lockJob($revision);

            $this->ensureStatus($job->status, ['booked'], 'Job can only be marked as completed from booked.');
            $this->ensureCurrentWorkingRevision($job, $revision, 'Only the current working revision can be marked as completed.');

            $job->forceFill([
                'status' => 'completed',
                'current_working_revision_id' => $revision->id,
                'completed_at' => now(),
            ])->save();
        });
    }

    private function priceRevision(JobRevision $revision, ?string $manualFinalTotal = null): JobRevision
    {
        $hasFuelCorrectionSelection = $this->hasMatchingFuelCorrectionSelection($revision);

        try {
            $this->pricingEngine->price($revision->fresh(), $manualFinalTotal);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'pricing' => $exception->getMessage(),
            ]);
        }

        $pricedRevision = $revision->fresh([
            'job.customer',
            'routeLegs',
            'weeklyFuelPrice',
            'rateSetting',
            'sharedRunAllocation.sharedRun',
        ]);

        return $hasFuelCorrectionSelection
            ? $this->recordFuelCorrectionSelection($pricedRevision)
            : $pricedRevision;
    }

    private function recordFuelCorrectionSelection(JobRevision $revision): JobRevision
    {
        $calculationExplanation = $revision->calculation_explanation;
        $calculationExplanation['fuel_context']['selection_type'] = 'explicit_correction';
        $calculationExplanation['fuel_context']['selected_weekly_fuel_price_id'] = $revision->weekly_fuel_price_id;
        $revision->forceFill(['calculation_explanation' => $calculationExplanation])->save();

        return $revision->fresh();
    }

    private function hasMatchingFuelCorrectionSelection(JobRevision $revision): bool
    {
        return data_get($revision->calculation_explanation, 'fuel_context.selection_type') === 'explicit_correction'
            && data_get($revision->calculation_explanation, 'fuel_context.selected_weekly_fuel_price_id') === $revision->weekly_fuel_price_id;
    }

    private function lockTransportEnquiry(TransportEnquiry $enquiry): TransportEnquiry
    {
        return TransportEnquiry::query()->whereKey($enquiry->id)->lockForUpdate()->firstOrFail();
    }

    private function lockRouteResolution(RouteResolution $resolution): RouteResolution
    {
        return RouteResolution::query()->whereKey($resolution->id)->lockForUpdate()->firstOrFail();
    }

    private function ensureEligibleRouteResolution(TransportEnquiry $enquiry, RouteResolution $resolution): void
    {
        if ($resolution->transport_enquiry_id === $enquiry->id
            && $this->canCreateDraftFromRouteResolution($resolution)) {
            return;
        }

        throw ValidationException::withMessages([
            'route' => 'This route cannot be used for pricing.',
        ]);
    }

    private function recordReviewAcceptance(RouteResolution $resolution, ?int $actorId): void
    {
        if ($resolution->overall_status !== 'operator_review_required') {
            return;
        }

        $this->ensureExceptionActorAuthorized($actorId);

        RouteResolutionReviewDecision::query()->firstOrCreate([
            'route_resolution_id' => $resolution->id,
            'decision' => 'accepted_for_pricing',
        ], [
            'actor_id' => $actorId,
            'recorded_at' => now(),
        ]);
    }

    private function canCreateDraftFromRouteResolution(RouteResolution $resolution): bool
    {
        if ($resolution->pricing_eligible
            && in_array($resolution->overall_status, ['resolved', 'resolved_with_warning'], true)) {
            return true;
        }

        return $resolution->overall_status === 'operator_review_required'
            && $resolution->legs()->whereNotNull('quoted_miles')->count() === 3;
    }

    private function existingRouteFirstRevision(TransportEnquiry $enquiry): ?JobRevision
    {
        if ($enquiry->quote_job_id === null) {
            return null;
        }

        return JobRevision::query()
            ->where('job_id', $enquiry->quote_job_id)
            ->orderByDesc('revision_number')
            ->firstOrFail();
    }

    private function ensurePricingContextAvailable(): void
    {
        $pricingContext = $this->pricingContextResolver->resolveForDraftWorkspace();

        if ($pricingContext['weekly_fuel_price'] !== null && $pricingContext['rate_setting'] !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'pricing' => 'This quote cannot be priced because the active fuel or rate context is unavailable. Ask the responsible staff member to update it.',
        ]);
    }

    private function createRouteFirstRevision(TransportEnquiry $enquiry, RouteResolution $resolution): JobRevision
    {
        $revision = $this->createRouteFirstRevisionWithoutRouteLegs($enquiry, $resolution);
        $this->createAutomaticRouteLegs($revision, $resolution);

        return $revision;
    }

    private function createRouteFirstRevisionWithoutRouteLegs(TransportEnquiry $enquiry, RouteResolution $resolution): JobRevision
    {
        $customer = Customer::query()->create($this->customerAttributesFromEnquiry($enquiry));
        $job = Job::query()->create(['customer_id' => $customer->id, 'status' => 'draft']);
        $revision = JobRevision::query()->create($this->routeFirstRevisionAttributes($enquiry, $resolution, $job));

        $job->forceFill(['current_working_revision_id' => $revision->id])->save();

        return $revision;
    }

    private function customerAttributesFromEnquiry(TransportEnquiry $enquiry): array
    {
        return [
            'name' => $enquiry->customer_name,
            'email' => $enquiry->email,
            'phone' => $enquiry->phone,
            'postcode' => $enquiry->pickup_postcode,
            'notes' => $enquiry->special_constraints,
        ];
    }

    private function routeFirstRevisionAttributes(TransportEnquiry $enquiry, RouteResolution $resolution, Job $job): array
    {
        return [
            'job_id' => $job->id,
            'route_resolution_id' => $resolution->id,
            'revision_number' => 1,
            'horse_count' => $enquiry->horse_count,
            'pickup_postcode' => $enquiry->pickup_postcode,
            'dropoff_postcode' => $enquiry->dropoff_postcode,
            'notes' => $enquiry->special_constraints,
        ];
    }

    private function createAutomaticRouteLegs(JobRevision $revision, RouteResolution $resolution): void
    {
        foreach ($this->automaticRouteLegs($resolution) as $index => [$definition, $routeResolutionLeg]) {
            RouteLeg::query()->create([
                'job_revision_id' => $revision->id,
                'sequence' => $index + 1,
                'label' => $definition['label'],
                'start_postcode' => $routeResolutionLeg->origin_input,
                'end_postcode' => $routeResolutionLeg->destination_input,
                'miles' => $routeResolutionLeg->quoted_miles,
                'manual_miles' => null,
                'rate_type' => $definition['rate_type'],
            ]);
        }
    }

    private function replaceAutomaticRouteLegsWithManualFallback(
        JobRevision $revision,
        RouteResolution $resolution,
        array $attributes,
        int $actorId,
    ): void {
        $resolutionLegs = $resolution->legs()->orderBy('sequence')->get()->keyBy('sequence');
        $routeLegs = $revision->routeLegs()->orderBy('sequence')->get()->keyBy('sequence');
        $changedLegCount = 0;

        if ($routeLegs->isEmpty()) {
            $this->createManualFallbackRouteLegs($revision, $resolutionLegs, $attributes['route_legs']);
            $routeLegs = $revision->routeLegs()->orderBy('sequence')->get()->keyBy('sequence');
        }

        foreach ($attributes['route_legs'] as $index => $routeLegAttributes) {
            $sequence = $index + 1;
            $routeLeg = $routeLegs->get($sequence);
            $resolutionLeg = $resolutionLegs->get($sequence);
            $manualMiles = (int) $routeLegAttributes['miles'];
            $originalMiles = $resolutionLeg?->quoted_miles;

            $routeLeg->forceFill([
                'miles' => $originalMiles ?? $manualMiles,
                'manual_miles' => $originalMiles === $manualMiles ? null : $manualMiles,
            ])->save();

            if ($originalMiles === $manualMiles) {
                continue;
            }

            $this->recordRouteLegException(
                $revision,
                $routeLeg,
                $resolutionLeg,
                'manual_route_fallback',
                $attributes['reason_category'],
                $attributes['explanation'],
                $originalMiles,
                $manualMiles,
                $actorId,
            );
            $changedLegCount++;
        }

        if ($changedLegCount === 0) {
            throw ValidationException::withMessages(['route_legs' => 'Enter at least one manual mile value that differs from the recorded route.']);
        }
    }

    private function createManualFallbackRouteLegs(JobRevision $revision, Collection $resolutionLegs, array $routeLegAttributes): void
    {
        $sequence = 1;

        foreach (self::AUTOMATIC_ROUTE_LEG_DEFINITIONS as $definition) {
            $resolutionLeg = $resolutionLegs->get($sequence);
            $manualMiles = (int) $routeLegAttributes[$sequence - 1]['miles'];

            RouteLeg::query()->create([
                'job_revision_id' => $revision->id,
                'sequence' => $sequence,
                'label' => $definition['label'],
                'start_postcode' => $resolutionLeg->origin_input,
                'end_postcode' => $resolutionLeg->destination_input,
                'miles' => $resolutionLeg->quoted_miles ?? $manualMiles,
                'manual_miles' => null,
                'rate_type' => $definition['rate_type'],
            ]);
            $sequence++;
        }
    }

    private function automaticRouteLegs(RouteResolution $resolution): array
    {
        $legsByType = $resolution->legs()->get()->groupBy('leg_type');
        $automaticRouteLegs = [];

        foreach (self::AUTOMATIC_ROUTE_LEG_DEFINITIONS as $legType => $definition) {
            $legs = $legsByType->get($legType, collect());
            $routeResolutionLeg = $legs->sole();

            if ($routeResolutionLeg->quoted_miles === null) {
                throw ValidationException::withMessages(['route' => 'All three route legs are required before this quote can be priced.']);
            }

            $automaticRouteLegs[] = [$definition, $routeResolutionLeg];
        }

        return $automaticRouteLegs;
    }

    private function customerAttributes(array $attributes): array
    {
        return [
            'name' => $attributes['customer_name'],
            'contact_name' => $attributes['customer_contact_name'] ?? null,
            'email' => $attributes['customer_email'] ?? null,
            'phone' => $attributes['customer_phone'] ?? null,
            'postcode' => $attributes['customer_postcode'] ?? null,
            'notes' => $attributes['customer_notes'] ?? null,
        ];
    }

    private function revisionAttributes(array $attributes): array
    {
        return [
            'horse_count' => $attributes['horse_count'],
            'pickup_postcode' => $attributes['pickup_postcode'],
            'dropoff_postcode' => $attributes['dropoff_postcode'],
            'notes' => $attributes['revision_notes'] ?? null,
            'final_total' => $attributes['manual_final_total'] ?? null,
            'manual_final_total_reason' => $attributes['manual_final_total_reason'] ?? null,
        ];
    }

    private function syncRouteLegs(JobRevision $revision, array $routeLegAttributes): void
    {
        $depotPostcode = $revision->depot_postcode_override ?? $this->resolveDepotPostcode($revision);

        foreach (DeterministicPricingCalculator::STANDARD_SINGLE_QUOTE_LEGS as $index => $definition) {
            $miles = (int) round((float) $routeLegAttributes[$index]['miles']);
            [$startPostcode, $endPostcode] = match ($definition['label']) {
                'depot_to_pickup' => [$depotPostcode, $revision->pickup_postcode],
                'pickup_to_dropoff' => [$revision->pickup_postcode, $revision->dropoff_postcode],
                'dropoff_to_depot' => [$revision->dropoff_postcode, $depotPostcode],
            };

            RouteLeg::query()->updateOrCreate(
                [
                    'job_revision_id' => $revision->id,
                    'sequence' => $index + 1,
                ],
                [
                    'label' => $definition['label'],
                    'start_postcode' => $startPostcode,
                    'end_postcode' => $endPostcode,
                    'miles' => $miles,
                    'manual_miles' => $miles,
                    'rate_type' => $definition['rate_type'],
                    'rate_per_mile' => null,
                    'amount' => null,
                ],
            );
        }

        $revision->routeLegs()
            ->whereNotIn('sequence', [1, 2, 3])
            ->delete();
    }

    private function resolveDepotPostcode(JobRevision $revision): ?string
    {
        return $this->pricingContextResolver
            ->resolve($revision)['rate_setting']?->depot_postcode;
    }

    private function lockJob(JobRevision $revision): Job
    {
        return Job::query()
            ->whereKey($revision->job_id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function nextRevisionNumber(Job $job): int
    {
        return (int) $job->revisions()->max('revision_number') + 1;
    }

    private function duplicateSharedRunAllocation(JobRevision $fromRevision, JobRevision $toRevision): void
    {
        $allocation = $fromRevision->sharedRunAllocation()->first();

        if ($allocation === null) {
            return;
        }

        $allocation->replicate()
            ->forceFill([
                'job_revision_id' => $toRevision->id,
            ])
            ->save();
    }

    private function duplicateRevision(
        Job $job,
        JobRevision $revision,
        array $overrides = [],
        array $excludedExceptionTypes = [],
        bool $copySharedRunAllocation = true,
    ): JobRevision {
        $nextRevision = JobRevision::query()->create(array_merge([
            'job_id' => $job->id,
            'weekly_fuel_price_id' => $revision->weekly_fuel_price_id,
            'rate_setting_id' => $revision->rate_setting_id,
            'route_resolution_id' => $revision->route_resolution_id,
            'revision_number' => $this->nextRevisionNumber($job),
            'horse_count' => $revision->horse_count,
            'depot_postcode_override' => $revision->depot_postcode_override,
            'pickup_postcode' => $revision->pickup_postcode,
            'dropoff_postcode' => $revision->dropoff_postcode,
            'notes' => $revision->notes,
            'engine_total' => $revision->engine_total,
            'final_total' => $revision->final_total,
            'manual_final_total_reason' => $revision->manual_final_total_reason,
            'calculation_explanation' => $revision->calculation_explanation,
        ], $overrides));

        $routeLegIds = $this->duplicateRouteLegs($revision, $nextRevision);
        if ($copySharedRunAllocation) {
            $this->duplicateSharedRunAllocation($revision, $nextRevision);
        }
        $this->duplicateExceptionAudits($revision, $nextRevision, $routeLegIds, $excludedExceptionTypes);

        return $nextRevision;
    }

    private function duplicateRouteLegs(JobRevision $fromRevision, JobRevision $toRevision): array
    {
        $routeLegIds = [];

        foreach ($fromRevision->routeLegs()->orderBy('sequence')->get() as $routeLeg) {
            $copiedRouteLeg = $routeLeg->replicate();
            $copiedRouteLeg->forceFill(['job_revision_id' => $toRevision->id])->save();
            $routeLegIds[$routeLeg->id] = $copiedRouteLeg->id;
        }

        return $routeLegIds;
    }

    private function duplicateExceptionAudits(
        JobRevision $fromRevision,
        JobRevision $toRevision,
        array $routeLegIds,
        array $excludedExceptionTypes,
    ): void {
        $audits = $fromRevision->quoteExceptionAudits()
            ->whereNotIn('exception_type', $excludedExceptionTypes)
            ->get();

        foreach ($audits as $audit) {
            $audit->replicate()
                ->forceFill([
                    'job_revision_id' => $toRevision->id,
                    'route_leg_id' => $routeLegIds[$audit->route_leg_id] ?? null,
                    'source_quote_exception_audit_id' => $audit->source_quote_exception_audit_id ?? $audit->id,
                ])
                ->save();
        }
    }

    private function applyRouteLegOverrides(JobRevision $revision, array $overrides, int $actorId): void
    {
        $routeLegs = $revision->routeLegs()->orderBy('sequence')->get()->keyBy('sequence');
        $resolutionLegs = $revision->routeResolution?->legs()->orderBy('sequence')->get()->keyBy('sequence') ?? collect();
        $changedLegCount = 0;

        foreach ($overrides as $index => $attributes) {
            if (blank($attributes['miles'] ?? null)) {
                continue;
            }

            $sequence = (int) $index + 1;
            $routeLeg = $routeLegs->get($sequence);
            if ($routeLeg === null) {
                throw ValidationException::withMessages(['overrides' => 'Only the three recorded route legs can be overridden.']);
            }

            $replacementMiles = (int) $attributes['miles'];
            $originalMiles = $routeLeg->manual_miles ?? $routeLeg->miles;
            if ($replacementMiles === (int) $originalMiles) {
                throw ValidationException::withMessages(["overrides.{$index}.miles" => 'Enter a mile value different from the recorded route.']);
            }

            $routeLeg->forceFill(['manual_miles' => $replacementMiles])->save();
            $this->recordRouteLegException(
                $revision,
                $routeLeg,
                $resolutionLegs->get($sequence),
                'route_leg_override',
                $attributes['reason_category'],
                $attributes['explanation'],
                $originalMiles,
                $replacementMiles,
                $actorId,
            );
            $changedLegCount++;
        }

        if ($changedLegCount === 0) {
            throw ValidationException::withMessages(['overrides' => 'Choose at least one route leg to override.']);
        }
    }

    private function recordRouteLegException(
        JobRevision $revision,
        RouteLeg $routeLeg,
        ?RouteResolutionLeg $resolutionLeg,
        string $exceptionType,
        string $reasonCategory,
        string $explanation,
        ?int $originalMiles,
        int $replacementMiles,
        int $actorId,
    ): void {
        QuoteExceptionAudit::query()->create([
            'job_revision_id' => $revision->id,
            'source_quote_exception_audit_id' => null,
            'route_leg_id' => $routeLeg->id,
            'route_resolution_leg_id' => $resolutionLeg?->id,
            'actor_id' => $actorId,
            'exception_type' => $exceptionType,
            'reason_category' => $reasonCategory,
            'explanation' => $explanation,
            'original_value' => $originalMiles === null ? null : (string) $originalMiles,
            'replacement_value' => (string) $replacementMiles,
            'recorded_at' => now(),
        ]);
    }

    private function recordFinalTotalOverride(JobRevision $nextRevision, array $attributes, int $actorId): void
    {
        QuoteExceptionAudit::query()->create([
            'job_revision_id' => $nextRevision->id,
            'source_quote_exception_audit_id' => null,
            'actor_id' => $actorId,
            'exception_type' => 'final_total_override',
            'reason_category' => $attributes['reason_category'],
            'explanation' => $attributes['explanation'],
            'original_value' => (string) $nextRevision->engine_total,
            'replacement_value' => number_format((float) $attributes['final_total'], 2, '.', ''),
            'recorded_at' => now(),
        ]);
    }

    private function ensureCurrentWorkingRevision(Job $job, JobRevision $revision, string $message): void
    {
        if ((int) $job->current_working_revision_id === (int) $revision->id) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => $message,
        ]);
    }

    private function auditedFinalTotal(JobRevision $revision): ?string
    {
        $hasOverride = $revision->quoteExceptionAudits()
            ->where('exception_type', 'final_total_override')
            ->exists();

        return $hasOverride ? $revision->final_total : null;
    }

    private function ensureRouteFirstRevisionCannotBeUpdated(JobRevision $revision): void
    {
        if ($revision->route_resolution_id === null) {
            return;
        }

        throw ValidationException::withMessages([
            'route' => 'This draft uses an automatic route and cannot be updated through the manual quote workspace.',
        ]);
    }

    private function ensureExceptionActorAuthorized(?int $actorId): void
    {
        if ($actorId !== null && User::query()
            ->whereKey($actorId)
            ->where('can_manage_quote_exceptions', true)
            ->exists()) {
            return;
        }

        throw new AuthorizationException('You are not authorised to manage quote exceptions.');
    }

    private function ensureManualFallbackAvailable(TransportEnquiry $enquiry, RouteResolution $resolution): void
    {
        if ($resolution->transport_enquiry_id === $enquiry->id
            && in_array($resolution->overall_status, self::MANUAL_FALLBACK_STATUSES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'route' => 'Manual route miles are only available after a route exception.',
        ]);
    }

    private function ensureRouteLegOverridesAvailable(JobRevision $revision): void
    {
        if ($revision->routeResolution !== null
            && in_array($revision->routeResolution->overall_status, self::ROUTE_EXCEPTION_STATUSES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'route' => 'Route leg overrides are only available after a route exception.',
        ]);
    }

    private function ensureStatus(string $currentStatus, array $allowedStatuses, string $message): void
    {
        if (in_array($currentStatus, $allowedStatuses, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => $message,
        ]);
    }

    private function ensureIssueChecklistIsComplete(JobRevision $revision): void
    {
        $incompleteLabels = $this->issuedQuoteChecklist->incompleteLabels($revision);

        if ($incompleteLabels === []) {
            return;
        }

        throw ValidationException::withMessages([
            'issue_checklist' => 'Complete the issue checklist before issuing: '.implode(', ', $incompleteLabels).'.',
        ]);
    }

    private function issueReference(JobRevision $revision): string
    {
        return 'SWEQ-'.$revision->job_id.'-R'.$revision->revision_number;
    }
}
