<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveSharedRunRequest;
use App\Models\JobRevision;
use App\Models\SharedRun;
use App\Services\Quotes\SharedRunManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SharedRunController extends Controller
{
    public function __construct(
        private readonly SharedRunManager $sharedRunManager,
    ) {}

    public function create(Request $request): View
    {
        return view('shared-runs.form', [
            'sharedRun' => new SharedRun,
            'availableDraftRevisions' => $this->availableDraftRevisions(),
            'allocationRows' => $this->buildCreateAllocationRows($request->integer('seed_revision')),
            'formAction' => route('shared-runs.store'),
            'method' => 'POST',
        ]);
    }

    public function store(SaveSharedRunRequest $request): RedirectResponse
    {
        $sharedRun = $this->sharedRunManager->create($request->validated());

        return redirect()
            ->route('shared-runs.show', ['sharedRun' => $sharedRun])
            ->with('status', 'Shared run saved.');
    }

    public function show(SharedRun $sharedRun): View
    {
        $sharedRun->setRelation('allocations', $sharedRun->allocations()
            ->whereHas('jobRevision', fn ($query) => $query
                ->whereHas('job', fn ($query) => $query
                    ->whereColumn('jobs.current_working_revision_id', 'job_revisions.id')))
            ->with([
                'jobRevision.job.customer',
                'jobRevision.routeLegs' => fn ($query) => $query->orderBy('sequence'),
            ])
            ->get());

        return view('shared-runs.form', [
            'sharedRun' => $sharedRun,
            'availableDraftRevisions' => $this->availableDraftRevisions($sharedRun),
            'allocationRows' => $this->buildSharedRunAllocationRows($sharedRun),
            'formAction' => route('shared-runs.update', ['sharedRun' => $sharedRun]),
            'method' => 'PATCH',
        ]);
    }

    public function update(SharedRun $sharedRun, SaveSharedRunRequest $request): RedirectResponse
    {
        $this->sharedRunManager->update($sharedRun, $request->validated());

        return redirect()
            ->route('shared-runs.show', ['sharedRun' => $sharedRun])
            ->with('status', 'Shared run updated.');
    }

    private function availableDraftRevisions(?SharedRun $sharedRun = null)
    {
        return JobRevision::query()
            ->with(['job.customer', 'routeLegs' => fn ($query) => $query->orderBy('sequence')])
            ->whereHas('job', fn ($query) => $query
                ->where('status', 'draft')
                ->whereColumn('current_working_revision_id', 'job_revisions.id'))
            ->where(function ($query) use ($sharedRun): void {
                $query->whereDoesntHave('sharedRunAllocation');

                if ($sharedRun !== null) {
                    $query->orWhereHas('sharedRunAllocation', fn ($query) => $query->where('shared_run_id', $sharedRun->id));
                }
            })
            ->orderByDesc('updated_at')
            ->get();
    }

    private function buildCreateAllocationRows(?int $seedRevisionId): array
    {
        return [
            [
                'job_revision_id' => $seedRevisionId,
                'legs' => $this->emptyAllocationLegs(),
            ],
            [
                'job_revision_id' => null,
                'legs' => $this->emptyAllocationLegs(),
            ],
        ];
    }

    private function buildSharedRunAllocationRows(SharedRun $sharedRun): array
    {
        return $sharedRun->allocations
            ->sortBy('id')
            ->map(function ($allocation): array {
                $allocationLegs = collect($allocation->allocation_explanation['allocation_legs'] ?? [])
                    ->keyBy('label');

                return [
                    'job_revision_id' => $allocation->job_revision_id,
                    'legs' => collect($this->emptyAllocationLegs())
                        ->map(function (array $leg) use ($allocationLegs): array {
                            $savedLeg = $allocationLegs->get($leg['label'], []);

                            return array_merge($leg, [
                                'full_miles' => $savedLeg['full_miles'] ?? null,
                                'split_miles' => $savedLeg['split_miles'] ?? null,
                                'split_divisor' => $savedLeg['split_divisor'] ?? null,
                                'reason' => $savedLeg['reason'] ?? null,
                            ]);
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function emptyAllocationLegs(): array
    {
        return [
            ['label' => 'depot_to_pickup', 'full_miles' => null, 'split_miles' => null, 'split_divisor' => null, 'reason' => null],
            ['label' => 'pickup_to_dropoff', 'full_miles' => null, 'split_miles' => null, 'split_divisor' => null, 'reason' => null],
            ['label' => 'dropoff_to_depot', 'full_miles' => null, 'split_miles' => null, 'split_divisor' => null, 'reason' => null],
        ];
    }
}
