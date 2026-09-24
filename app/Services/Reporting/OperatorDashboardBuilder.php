<?php

namespace App\Services\Reporting;

use App\Models\Job;
use App\Models\JobRevision;
use App\Models\RateSetting;
use App\Models\RouteLeg;
use App\Models\TransportDay;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class OperatorDashboardBuilder
{
    public function build(?CarbonInterface $now = null): array
    {
        $today = ($now ?? now())->copy()->startOfDay();
        $windowEnd = $today->copy()->addDays(6)->endOfDay();
        $jobs = $this->jobs();
        $days = $this->days($today, $windowEnd);

        return [
            'summary' => $this->summary($jobs, $days, $today),
            'days' => $this->dayRows($days, $jobs),
            'unassigned_jobs' => $this->unassignedRows($jobs),
            'date_range' => $this->dateRange($today, $windowEnd),
            'display_date' => $today->format('l j F Y'),
        ];
    }

    private function jobs(): Collection
    {
        return Job::query()
            ->with([
                'customer',
                'transportDay',
                'currentWorkingRevision.routeResolution.legs',
                'currentWorkingRevision.routeLegs',
                'issuedRevision.routeResolution.legs',
                'issuedRevision.routeLegs',
                'acceptedRevision.routeResolution.legs',
                'acceptedRevision.routeLegs',
            ])
            ->orderByDesc('updated_at')
            ->get();
    }

    private function days(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return TransportDay::query()
            ->whereBetween('run_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('run_date')
            ->orderBy('id')
            ->get();
    }

    private function summary(Collection $jobs, Collection $days, CarbonInterface $today): array
    {
        $todayJobs = $jobs->filter(fn (Job $job): bool => $job->transportDay?->run_date?->isSameDay($today) === true);
        $pendingJobs = $jobs->where('status', 'pending');
        $bookedAhead = $this->bookedAhead($jobs, $today);
        $recentlyCompleted = $this->recentlyCompleted($jobs, $today);
        $openQuotes = $jobs->whereIn('status', ['quoted', 'pending']);
        $todayDayCount = $days->filter(fn (TransportDay $day): bool => $day->run_date?->isSameDay($today) === true)->count();

        return [
            $this->metric('Today', $todayDayCount.' '.str('day')->plural($todayDayCount), $todayJobs->count().' '.str('job')->plural($todayJobs->count()).' scheduled today', 'calendar'),
            $this->metric('Awaiting response', $pendingJobs->count().' '.str('quote')->plural($pendingJobs->count()), 'Pending now', 'document'),
            $this->metric('Booked ahead', $bookedAhead->count().' '.str('job')->plural($bookedAhead->count()), 'Next 7 days', 'truck'),
            $this->metric('Completed', $recentlyCompleted->count().' '.str('job')->plural($recentlyCompleted->count()), 'Last 7 days', 'check'),
            $this->metric('Open quoted value', $this->currency($this->quoteValue($openQuotes)), 'Pending and quoted jobs', 'chart'),
        ];
    }

    private function bookedAhead(Collection $jobs, CarbonInterface $today): Collection
    {
        $windowEnd = $today->copy()->addDays(7)->endOfDay();

        return $jobs->filter(fn (Job $job): bool => $job->status === 'booked'
            && $job->transportDay?->run_date?->between($today->copy()->addDay(), $windowEnd) === true);
    }

    private function recentlyCompleted(Collection $jobs, CarbonInterface $today): Collection
    {
        $start = $today->copy()->subDays(6);
        $end = $today->copy()->endOfDay();

        return $jobs->filter(fn (Job $job): bool => $job->status === 'completed'
            && $job->completed_at?->between($start, $end) === true);
    }

    private function metric(string $label, string $value, string $meta, string $icon): array
    {
        return compact('label', 'value', 'meta', 'icon');
    }

    private function quoteValue(Collection $jobs): float
    {
        return $jobs->sum(fn (Job $job): float => $this->authoritativeTotal($job->issuedRevision));
    }

    private function dayRows(Collection $days, Collection $jobs): array
    {
        $depot = $this->activeDepot();

        return $days->values()->map(function (TransportDay $day, int $index) use ($jobs, $depot): array {
            $dayJobs = $jobs->where('transport_day_id', $day->id)->sortBy('transport_day_sequence');
            $jobRows = $dayJobs->map(fn (Job $job): array => $this->jobRow($job))->values();
            $evidence = $this->routeEvidence($jobRows);

            return $this->dayRow($day, $jobRows, $evidence, $depot, $index === 0);
        })->all();
    }

    private function dayRow(TransportDay $day, Collection $jobs, array $evidence, ?string $depot, bool $isOpen): array
    {
        return [
            'date' => $day->run_date?->format('l j F Y'),
            'name' => $day->name,
            'depot' => $day->depot_postcode ?? $depot ?? 'Depot not recorded',
            'job_count' => $jobs->count(),
            'horse_count' => $jobs->sum('horse_count'),
            'miles' => $evidence['miles'],
            'duration' => $evidence['duration'],
            'is_open' => $isOpen,
            'jobs' => $jobs->all(),
            'manage_url' => route('transport-days.show', ['transportDay' => $day]),
        ];
    }

    private function jobRow(Job $job): array
    {
        $revision = $this->lifecycleRevision($job);

        return [
            'id' => $job->id,
            'sequence' => $job->transport_day_sequence,
            'customer' => $job->customer->name,
            'horse_count' => $revision?->horse_count ?? 0,
            'pickup' => $revision?->pickup_postcode,
            'dropoff' => $revision?->dropoff_postcode,
            'status' => $job->status,
            'value' => $revision === null ? null : $this->currency($this->authoritativeTotal($revision)),
            'revision' => $revision,
            'action_url' => $revision === null ? null : route('jobs.revisions.show', ['job' => $job, 'revision' => $revision]),
        ];
    }

    private function lifecycleRevision(Job $job): ?JobRevision
    {
        return match ($job->status) {
            'quoted', 'pending' => $job->issuedRevision,
            'booked', 'completed' => $job->acceptedRevision,
            default => $job->currentWorkingRevision,
        };
    }

    private function routeEvidence(Collection $jobs): array
    {
        return [
            'miles' => $this->routeMiles($jobs),
            'duration' => $this->routeDuration($jobs),
        ];
    }

    private function routeMiles(Collection $jobs): ?int
    {
        if ($jobs->isEmpty() || $jobs->contains(fn (array $job): bool => ! $this->hasMileageEvidence($job['revision']))) {
            return null;
        }

        return $jobs->flatMap(fn (array $job): Collection => $job['revision']->routeLegs)
            ->sum(fn (RouteLeg $leg): int => $leg->manual_miles ?? $leg->miles);
    }

    private function routeDuration(Collection $jobs): ?string
    {
        if ($jobs->isEmpty() || $jobs->contains(fn (array $job): bool => ! $this->hasDurationEvidence($job['revision']))) {
            return null;
        }

        $legs = $jobs->flatMap(fn (array $job): Collection => $job['revision']->routeResolution->legs);

        return $this->duration($legs->sum('duration_seconds'));
    }

    private function hasDurationEvidence(?JobRevision $revision): bool
    {
        $legs = $revision?->routeResolution?->legs;

        return $this->hasCompleteThreeLegs(
            $legs,
            fn ($leg): bool => $leg->duration_seconds !== null,
        );
    }

    private function hasMileageEvidence(?JobRevision $revision): bool
    {
        $legs = $revision?->routeLegs;

        return $this->hasCompleteThreeLegs(
            $legs,
            fn (RouteLeg $leg): bool => ($leg->manual_miles ?? $leg->miles) !== null,
        );
    }

    private function hasCompleteThreeLegs(?Collection $legs, callable $hasValue): bool
    {
        return $legs !== null
            && $legs->count() === 3
            && $legs->pluck('sequence')->sort()->values()->all() === [1, 2, 3]
            && $legs->every($hasValue);
    }

    private function unassignedRows(Collection $jobs): Collection
    {
        return $jobs->whereNull('transport_day_id')
            ->reject(fn (Job $job): bool => $job->status === 'lost')
            ->map(fn (Job $job): array => $this->jobRow($job))
            ->values();
    }

    private function activeDepot(): ?string
    {
        return RateSetting::query()->where('is_active', true)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('depot_postcode');
    }

    private function authoritativeTotal(?JobRevision $revision): float
    {
        return (float) ($revision?->final_total ?? $revision?->engine_total ?? 0);
    }

    private function currency(float $amount): string
    {
        $decimals = floor($amount) === $amount ? 0 : 2;

        return '£'.number_format($amount, $decimals);
    }

    private function duration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0 ? "{$hours} hr {$minutes} min" : "{$minutes} min";
    }

    private function dateRange(CarbonInterface $start, CarbonInterface $end): string
    {
        return $start->isSameMonth($end)
            ? $start->format('j').' - '.$end->format('j M Y')
            : $start->format('j M').' - '.$end->format('j M Y');
    }
}
