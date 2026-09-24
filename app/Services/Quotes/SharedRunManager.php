<?php

namespace App\Services\Quotes;

use App\Models\JobRevision;
use App\Models\SharedRun;
use App\Models\SharedRunAllocation;
use App\Services\Pricing\DeterministicPricingCalculator;
use App\Services\Pricing\JobRevisionPricingEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SharedRunManager
{
    public function __construct(
        private readonly JobRevisionPricingEngine $pricingEngine,
        private readonly QuoteWorkspaceManager $quoteWorkspaceManager,
    ) {}

    public function create(array $attributes): SharedRun
    {
        return $this->persist(new SharedRun, $attributes);
    }

    public function update(SharedRun $sharedRun, array $attributes): SharedRun
    {
        return $this->persist($sharedRun, $attributes);
    }

    private function persist(SharedRun $sharedRun, array $attributes): SharedRun
    {
        try {
            return DB::transaction(function () use ($sharedRun, $attributes): SharedRun {
                $sharedRun->forceFill($this->sharedRunAttributes($attributes))->save();

                $detachedRevisionIds = $this->syncAllocations($sharedRun, $attributes['allocations']);
                $this->fillDepotPostcode($sharedRun);
                $this->priceAllocations($sharedRun);
                $this->repriceDetachedRevisions($detachedRevisionIds);

                return $this->loadSharedRun($sharedRun);
            });
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'pricing' => $exception->getMessage(),
            ]);
        }
    }

    private function sharedRunAttributes(array $attributes): array
    {
        return [
            'name' => $attributes['name'] ?? null,
            'run_date' => $attributes['run_date'] ?? null,
            'notes' => $attributes['notes'] ?? null,
        ];
    }

    private function syncAllocations(SharedRun $sharedRun, array $allocations): array
    {
        $detachedRevisionIds = $this->prepareDetachedRevisions($sharedRun, $allocations);
        $jobRevisionIds = collect($allocations)
            ->map(fn (array $attributes): int => $this->syncAllocation($sharedRun, $attributes))
            ->all();

        $this->currentAllocationsQuery($sharedRun)
            ->whereNotIn('job_revision_id', $jobRevisionIds)
            ->delete();

        return $detachedRevisionIds;
    }

    private function prepareDetachedRevisions(SharedRun $sharedRun, array $allocations): array
    {
        return $this->currentAllocationsQuery($sharedRun)
            ->whereNotIn('job_revision_id', collect($allocations)->pluck('job_revision_id'))
            ->with('jobRevision')
            ->get()
            ->pluck('jobRevision')
            ->map(fn (JobRevision $revision): int => $this->quoteWorkspaceManager
                ->reviseForSharedAllocationChange($revision, false)->id)
            ->all();
    }

    private function syncAllocation(SharedRun $sharedRun, array $attributes): int
    {
        $revision = JobRevision::query()->findOrFail($attributes['job_revision_id']);
        $revision = $this->quoteWorkspaceManager->reviseForSharedAllocationChange($revision);

        SharedRunAllocation::query()->updateOrCreate(
            ['shared_run_id' => $sharedRun->id, 'job_revision_id' => $revision->id],
            ['allocation_explanation' => $this->buildAllocationExplanation($attributes)],
        );

        return $revision->id;
    }

    private function buildAllocationExplanation(array $allocationAttributes): array
    {
        $allocationLegs = [];

        foreach (DeterministicPricingCalculator::STANDARD_SINGLE_QUOTE_LEGS as $index => $definition) {
            $leg = $allocationAttributes['legs'][$index];

            $allocationLegs[] = [
                'label' => $definition['label'],
                'full_miles' => (int) round((float) $leg['full_miles']),
                'split_miles' => (int) round((float) $leg['split_miles']),
                'split_divisor' => $leg['split_divisor'] === null ? null : (int) $leg['split_divisor'],
                'reason' => trim((string) $leg['reason']),
            ];
        }

        return [
            'type' => 'shared_run',
            'allocation_legs' => $allocationLegs,
        ];
    }

    private function fillDepotPostcode(SharedRun $sharedRun): void
    {
        $depotPostcode = $this->currentAllocationsQuery($sharedRun)
            ->with(['jobRevision.routeLegs' => fn ($query) => $query->orderBy('sequence')])
            ->get()
            ->first()?->jobRevision?->routeLegs
            ?->first()?->start_postcode;

        $sharedRun->forceFill([
            'depot_postcode' => $depotPostcode,
        ])->save();
    }

    private function priceAllocations(SharedRun $sharedRun): void
    {
        $allocations = $this->currentAllocationsQuery($sharedRun)
            ->with('jobRevision')
            ->get();

        foreach ($allocations as $allocation) {
            $this->pricingEngine->price($allocation->jobRevision);
        }
    }

    private function repriceDetachedRevisions(array $detachedRevisionIds): void
    {
        if ($detachedRevisionIds === []) {
            return;
        }

        $detachedRevisions = JobRevision::query()
            ->whereKey($detachedRevisionIds)
            ->get();

        foreach ($detachedRevisions as $detachedRevision) {
            $this->pricingEngine->price($detachedRevision);
        }
    }

    private function loadSharedRun(SharedRun $sharedRun): SharedRun
    {
        $sharedRun->refresh();
        $sharedRun->setRelation('allocations', $this->currentAllocationsQuery($sharedRun)
            ->with([
                'jobRevision.job.customer',
                'jobRevision.routeLegs' => fn ($query) => $query->orderBy('sequence'),
            ])
            ->get());

        return $sharedRun;
    }

    private function currentAllocationsQuery(SharedRun $sharedRun)
    {
        return $sharedRun->allocations()
            ->whereHas('jobRevision', fn ($query) => $query
                ->whereHas('job', fn ($query) => $query
                    ->whereColumn('jobs.current_working_revision_id', 'job_revisions.id')));
    }
}
