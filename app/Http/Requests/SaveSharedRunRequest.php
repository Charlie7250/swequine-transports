<?php

namespace App\Http\Requests;

use App\Models\JobRevision;
use App\Services\Pricing\DeterministicPricingCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveSharedRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $allocations = collect($this->input('allocations', []))
            ->map(function (mixed $allocation): mixed {
                if (! is_array($allocation)) {
                    return $allocation;
                }

                $allocation['legs'] = collect($allocation['legs'] ?? [])
                    ->map(function (mixed $leg): mixed {
                        if (! is_array($leg)) {
                            return $leg;
                        }

                        $leg['split_divisor'] = $leg['split_divisor'] === '' ? null : $leg['split_divisor'];

                        return $leg;
                    })
                    ->all();

                return $allocation;
            })
            ->all();

        $this->merge([
            'name' => $this->filled('name') ? $this->input('name') : null,
            'run_date' => $this->filled('run_date') ? $this->input('run_date') : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
            'allocations' => $allocations,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'run_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.job_revision_id' => ['required', 'integer', 'distinct', 'exists:job_revisions,id'],
            'allocations.*.legs' => ['required', 'array', 'size:3'],
            'allocations.*.legs.0' => ['required', 'array'],
            'allocations.*.legs.1' => ['required', 'array'],
            'allocations.*.legs.2' => ['required', 'array'],
            'allocations.*.legs.*.full_miles' => ['required', 'integer', 'min:0'],
            'allocations.*.legs.*.split_miles' => ['required', 'integer', 'min:0'],
            'allocations.*.legs.*.split_divisor' => ['nullable', 'integer', 'min:2'],
            'allocations.*.legs.*.reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'allocations.required' => 'Add at least one customer allocation before saving a shared run.',
            'allocations.*.job_revision_id.required' => 'Choose a draft quote revision for each customer allocation.',
            'allocations.*.job_revision_id.distinct' => 'Each customer allocation must use a different draft quote revision.',
            'allocations.*.legs.size' => 'Each customer allocation must keep all three transport legs.',
            'allocations.*.legs.*.full_miles.required' => 'Enter the full-charge miles for this leg.',
            'allocations.*.legs.*.split_miles.required' => 'Enter the shared miles for this leg.',
            'allocations.*.legs.*.reason.required' => 'Explain why these miles belong to this customer.',
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateSharedAllocations($validator),
        ];
    }

    private function validateSharedAllocations(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        foreach ($this->input('allocations', []) as $allocationIndex => $allocation) {
            $revision = JobRevision::query()
                ->with(['job', 'routeLegs' => fn ($query) => $query->orderBy('sequence'), 'sharedRunAllocation'])
                ->find($allocation['job_revision_id']);

            if ($revision === null) {
                continue;
            }

            $this->validateRevisionStatus($validator, $revision, $allocationIndex);
            $this->validateExistingSharedRun($validator, $revision, $allocationIndex);
            $this->validateAllocationLegs($validator, $revision, $allocation['legs'], $allocationIndex);
        }

        if ($validator->errors()->isEmpty()) {
            $this->validateSplitConsistency($validator);
        }
    }

    private function validateRevisionStatus(Validator $validator, JobRevision $revision, int $allocationIndex): void
    {
        if ($revision->job->status === 'draft') {
            return;
        }

        $validator->errors()->add(
            "allocations.{$allocationIndex}.job_revision_id",
            'Shared runs can only attach draft quote revisions.',
        );
    }

    private function validateExistingSharedRun(Validator $validator, JobRevision $revision, int $allocationIndex): void
    {
        $currentSharedRunId = $this->route('sharedRun')?->id;
        $existingAllocation = $revision->sharedRunAllocation;

        if ($existingAllocation === null || $existingAllocation->shared_run_id === $currentSharedRunId) {
            return;
        }

        $validator->errors()->add(
            "allocations.{$allocationIndex}.job_revision_id",
            'This quote revision is already attached to another shared run.',
        );
    }

    private function validateAllocationLegs(
        Validator $validator,
        JobRevision $revision,
        array $legs,
        int $allocationIndex,
    ): void {
        $routeLegs = $revision->routeLegs->keyBy('sequence');

        foreach (DeterministicPricingCalculator::STANDARD_SINGLE_QUOTE_LEGS as $legIndex => $definition) {
            $routeLeg = $routeLegs->get($legIndex + 1);

            if ($routeLeg === null) {
                $validator->errors()->add(
                    "allocations.{$allocationIndex}.job_revision_id",
                    'Shared run allocations require the stored three-leg quote route.',
                );

                return;
            }

            $fullMiles = (int) round((float) $legs[$legIndex]['full_miles']);
            $splitMiles = (int) round((float) $legs[$legIndex]['split_miles']);
            $routeMiles = (int) ($routeLeg->manual_miles ?? $routeLeg->miles);

            if ($splitMiles > 0 && ($legs[$legIndex]['split_divisor'] ?? null) === null) {
                $validator->errors()->add(
                    "allocations.{$allocationIndex}.legs.{$legIndex}.split_divisor",
                    'Split mileage requires a split divisor.',
                );
            }

            if ($fullMiles + $splitMiles > $routeMiles) {
                $validator->errors()->add(
                    "allocations.{$allocationIndex}.legs.{$legIndex}.full_miles",
                    "Shared allocation miles cannot exceed the stored {$definition['label']} miles.",
                );
            }
        }
    }

    private function validateSplitConsistency(Validator $validator): void
    {
        foreach (DeterministicPricingCalculator::STANDARD_SINGLE_QUOTE_LEGS as $legIndex => $definition) {
            $sharedAllocations = collect($this->input('allocations', []))
                ->map(function (array $allocation, int $allocationIndex) use ($legIndex): ?array {
                    $splitMiles = (int) ($allocation['legs'][$legIndex]['split_miles'] ?? 0);

                    if ($splitMiles === 0) {
                        return null;
                    }

                    return [
                        'allocation_index' => $allocationIndex,
                        'split_divisor' => (int) ($allocation['legs'][$legIndex]['split_divisor'] ?? 0),
                    ];
                })
                ->filter()
                ->values();

            if ($sharedAllocations->isEmpty()) {
                continue;
            }

            $distinctDivisors = $sharedAllocations->pluck('split_divisor')->unique();
            $expectedDivisor = $sharedAllocations->count();

            if ($distinctDivisors->count() === 1 && $distinctDivisors->first() === $expectedDivisor) {
                continue;
            }

            foreach ($sharedAllocations as $sharedAllocation) {
                $validator->errors()->add(
                    "allocations.{$sharedAllocation['allocation_index']}.legs.{$legIndex}.split_divisor",
                    "Split divisors for {$definition['label']} must match the number of allocations sharing that leg.",
                );
            }
        }
    }
}
