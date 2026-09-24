@php
    $job = $entry['job'];
    $revision = $entry['revision'];
    $reportingDate = $entry['reporting_date'];
@endphp
<tr>
    <td>
        <x-status-badge :status="$entry['status']" />
    </td>
    <td>{{ $job->customer->name }}</td>
    <td>{{ $revision?->pickup_postcode ?? 'Pending' }} to {{ $revision?->dropoff_postcode ?? 'Pending' }}</td>
    <td>£{{ number_format((float) ($revision?->final_total ?? $revision?->engine_total ?? 0), 2) }}</td>
    <td>{{ $reportingDate?->format('j M Y') ?? 'Pending' }}</td>
    <td>
        @if ($revision)
            <a class="button-inline" href="{{ route('jobs.revisions.show', ['job' => $job, 'revision' => $revision]) }}">
                Revision {{ $revision->revision_number }}
            </a>
        @else
            Pending
        @endif
    </td>
</tr>
