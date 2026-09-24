<?php

namespace App\Services\Reporting;

use App\Models\JobRevision;
use App\Models\QuoteExceptionAudit;
use App\Models\RouteResolution;
use Illuminate\Support\Collection;

class ReleaseOneOperationalEvidenceBuilder
{
    private const FAILURE_STATUSES = [
        'invalid_input',
        'unresolved',
        'provider_unavailable',
        'provider_response_invalid',
    ];

    private const EXCEPTION_TYPES = [
        'manual_route_fallback',
        'route_leg_override',
        'final_total_override',
    ];

    public function build(): array
    {
        $resolutions = RouteResolution::query()
            ->whereNotNull('transport_enquiry_id')
            ->whereHas('transportEnquiry')
            ->get(['id', 'transport_enquiry_id', 'overall_status', 'failure_detail']);
        $revisions = JobRevision::query()
            ->whereNotNull('route_resolution_id')
            ->whereHas('routeResolution.transportEnquiry')
            ->with(['routeResolution.transportEnquiry', 'job', 'quoteExceptionAudits'])
            ->get(['id', 'job_id', 'route_resolution_id', 'created_at']);

        return [
            'attempts' => $this->attempts($resolutions),
            'rates' => $this->rates($revisions),
            'turnaround' => $this->turnaround($revisions),
            'exception_reasons' => $this->exceptionReasons($revisions),
            'issued_quote_revisions' => $revisions
                ->filter(fn (JobRevision $revision): bool => $revision->job?->issued_at !== null)
                ->pluck('id')
                ->unique()
                ->count(),
        ];
    }

    private function attempts(Collection $resolutions): array
    {
        $statuses = $resolutions
            ->map(fn (RouteResolution $resolution): string => $this->status($resolution->overall_status))
            ->countBy()
            ->map(fn (int $count, string $status): array => ['status' => $status, 'count' => $count])
            ->values()
            ->all();
        $failureCategories = $resolutions
            ->filter(fn (RouteResolution $resolution): bool => in_array($this->status($resolution->overall_status), self::FAILURE_STATUSES, true))
            ->map(fn (RouteResolution $resolution): string => $this->failureCategory($resolution))
            ->countBy()
            ->map(fn (int $count, string $category): array => ['category' => $category, 'count' => $count])
            ->values()
            ->all();

        return [
            'total' => $resolutions->pluck('id')->unique()->count(),
            'statuses' => $statuses,
            'by_status' => $statuses,
            'failure_categories' => $failureCategories,
        ];
    }

    private function rates(Collection $revisions): array
    {
        $denominator = $revisions->pluck('route_resolution_id')->filter()->unique()->count();
        $rates = [];

        foreach (self::EXCEPTION_TYPES as $exceptionType) {
            $numerator = $revisions
                ->filter(fn (JobRevision $revision): bool => $revision->quoteExceptionAudits
                    ->contains(fn ($audit): bool => $audit->exception_type === $exceptionType))
                ->pluck('route_resolution_id')
                ->filter()
                ->unique()
                ->count();
            $rates[$exceptionType] = $this->rate($numerator, $denominator);
        }

        return [
            'denominator' => $denominator,
            'manual_route_fallback' => $rates['manual_route_fallback'],
            'route_leg_override' => $rates['route_leg_override'],
            'final_total_override' => $rates['final_total_override'],
        ];
    }

    private function exceptionReasons(Collection $revisions): array
    {
        return $revisions
            ->filter(fn (JobRevision $revision): bool => $revision->routeResolution?->transportEnquiry?->quote_job_id !== null
                && (int) $revision->routeResolution->transportEnquiry->quote_job_id === (int) $revision->job_id)
            ->pluck('quoteExceptionAudits')
            ->flatten()
            ->filter(fn (QuoteExceptionAudit $audit): bool => $audit->source_quote_exception_audit_id === null)
            ->countBy(fn (QuoteExceptionAudit $audit): string => $this->reasonCategory($audit->reason_category))
            ->map(fn (int $count, string $category): array => ['category' => $category, 'count' => $count])
            ->sort(fn (array $left, array $right): int => $right['count'] <=> $left['count'] ?: $left['category'] <=> $right['category'])
            ->values()
            ->all();
    }

    private function turnaround(Collection $revisions): array
    {
        $chains = $revisions
            ->filter(fn (JobRevision $revision): bool => $revision->routeResolution?->transportEnquiry?->quote_job_id !== null
                && (int) $revision->routeResolution->transportEnquiry->quote_job_id === (int) $revision->job_id)
            ->groupBy(fn (JobRevision $revision): string => $revision->routeResolution->transport_enquiry_id.'-'.$revision->job_id)
            ->map(fn (Collection $chain): JobRevision => $chain
                ->sortBy(fn (JobRevision $revision): array => [$revision->created_at?->timestamp ?? PHP_INT_MAX, $revision->id])
                ->first());
        $quoteReady = $chains
            ->filter(fn (JobRevision $revision): bool => $revision->routeResolution->transportEnquiry->created_at !== null && $revision->created_at !== null)
            ->map(fn (JobRevision $revision): ?int => $this->duration($revision->routeResolution->transportEnquiry->created_at, $revision->created_at))
            ->filter(fn (?int $duration): bool => $duration !== null)
            ->values();
        $issued = $chains
            ->filter(fn (JobRevision $revision): bool => $revision->routeResolution->transportEnquiry->created_at !== null && $revision->job?->issued_at !== null)
            ->map(fn (JobRevision $revision): ?int => $this->duration($revision->routeResolution->transportEnquiry->created_at, $revision->job->issued_at))
            ->filter(fn (?int $duration): bool => $duration !== null)
            ->values();

        return [
            'quote_ready' => $this->durationMetric($quoteReady),
            'issued' => $this->durationMetric($issued),
        ];
    }

    private function durationMetric(Collection $durations): array
    {
        $count = $durations->count();

        if ($count === 0) {
            return ['median_seconds' => null, 'eligible_records' => 0, 'display' => 'N/A (0 records)'];
        }

        $sorted = $durations->sort()->values();
        $middle = intdiv($count, 2);
        $median = $count % 2 === 1
            ? $sorted[$middle]
            : (int) round(($sorted[$middle - 1] + $sorted[$middle]) / 2);

        return [
            'median_seconds' => $median,
            'eligible_records' => $count,
            'display' => $median.' seconds ('.$count.' records)',
        ];
    }

    private function rate(int $numerator, int $denominator): array
    {
        $percentage = $denominator === 0 ? null : round(($numerator / $denominator) * 100, 1);

        return [
            'numerator' => $numerator,
            'denominator' => $denominator,
            'percentage' => $percentage,
            'display' => $percentage === null
                ? $numerator.'/'.$denominator.' (N/A)'
                : $numerator.'/'.$denominator.' ('.number_format($percentage, 1).'%)',
        ];
    }

    private function status(?string $status): string
    {
        return trim((string) $status) === '' ? 'unknown' : trim($status);
    }

    private function reasonCategory(?string $reasonCategory): string
    {
        $category = trim((string) $reasonCategory);

        return $category === '' ? 'unknown' : $category;
    }

    private function failureCategory(RouteResolution $resolution): string
    {
        $code = $resolution->failure_detail['code'] ?? null;

        return in_array($code, self::FAILURE_STATUSES, true)
            ? $code
            : $this->status($resolution->overall_status);
    }

    private function duration($start, $end): ?int
    {
        $seconds = $start->diffInSeconds($end, false);

        return $seconds >= 0 ? $seconds : null;
    }
}
