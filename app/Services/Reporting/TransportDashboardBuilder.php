<?php

namespace App\Services\Reporting;

use App\Models\Job;
use App\Models\JobRevision;
use App\Models\TransportDay;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TransportDashboardBuilder
{
    private const PIPELINE_STATUSES = [
        'draft',
        'quoted',
        'pending',
        'booked',
        'completed',
        'lost',
    ];

    public function build(): array
    {
        $jobs = Job::query()
            ->with([
                'customer',
                'currentWorkingRevision',
                'issuedRevision',
                'acceptedRevision',
                'revisions',
            ])
            ->orderByDesc('updated_at')
            ->get();

        return [
            'job_count' => $jobs->count(),
            'pipeline' => collect(self::PIPELINE_STATUSES)
                ->map(fn (string $status): array => [
                    'status' => $status,
                    'label' => Str::headline($status),
                    'jobs' => $jobs
                        ->where('status', $status)
                        ->map(fn (Job $job): array => [
                            'job' => $job,
                            'reporting_date' => $this->reportingDate($job),
                            'revision' => $this->pipelineRevision($job),
                        ])
                        ->values(),
                ])
                ->all(),
            'reporting' => [
                'quote_value' => $this->buildMetric(
                    $jobs->filter(fn (Job $job): bool => $job->issued_at !== null && $job->issuedRevision !== null),
                    fn (Job $job): ?JobRevision => $job->issuedRevision,
                ),
                'booked_value' => $this->buildMetric(
                    $jobs->filter(fn (Job $job): bool => $job->booked_at !== null && $job->acceptedRevision !== null),
                    fn (Job $job): ?JobRevision => $job->acceptedRevision,
                ),
                'completed_revenue' => $this->buildMetric(
                    $jobs->filter(fn (Job $job): bool => $job->completed_at !== null && $job->acceptedRevision !== null),
                    fn (Job $job): ?JobRevision => $job->acceptedRevision,
                ),
            ],
            'days' => $this->buildDays($jobs),
            'unassigned_jobs' => $jobs
                ->whereNull('transport_day_id')
                ->map(fn (Job $job): array => $this->jobRow($job))
                ->values(),
        ];
    }

    private function buildDays(Collection $jobs): array
    {
        return TransportDay::query()
            ->orderByDesc('run_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TransportDay $day): array => [
                'day' => $day,
                'jobs' => $jobs
                    ->where('transport_day_id', $day->id)
                    ->sortBy('transport_day_sequence')
                    ->map(fn (Job $job): array => $this->jobRow($job))
                    ->values(),
            ])
            ->filter(fn (array $day): bool => $day['jobs']->isNotEmpty())
            ->values()
            ->all();
    }

    private function jobRow(Job $job): array
    {
        return [
            'job' => $job,
            'status' => $job->status,
            'status_label' => Str::headline($job->status),
            'reporting_date' => $this->reportingDate($job),
            'revision' => $this->pipelineRevision($job),
        ];
    }

    private function pipelineRevision(Job $job): ?JobRevision
    {
        return match ($job->status) {
            'quoted', 'pending' => $job->issuedRevision,
            'booked', 'completed' => $job->acceptedRevision,
            default => $job->currentWorkingRevision,
        };
    }

    private function reportingDate(Job $job)
    {
        return match ($job->status) {
            'quoted', 'pending' => $job->issued_at,
            'booked' => $job->booked_at,
            'completed' => $job->completed_at,
            default => null,
        };
    }

    private function buildMetric(Collection $jobs, callable $revisionResolver): array
    {
        return [
            'count' => $jobs->count(),
            'amount' => $jobs->sum(function (Job $job) use ($revisionResolver): float {
                return $this->authoritativeTotal($revisionResolver($job));
            }),
        ];
    }

    private function authoritativeTotal(?JobRevision $revision): float
    {
        if ($revision === null) {
            return 0.0;
        }

        return (float) ($revision->final_total ?? $revision->engine_total ?? 0);
    }
}
