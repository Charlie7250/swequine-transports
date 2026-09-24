@props(['status'])

@php
    $label = config("job_statuses.{$status}.label", \Illuminate\Support\Str::headline($status));
    $meaning = config("job_statuses.{$status}.meaning");
@endphp

<span {{ $attributes->merge(['class' => "job-status job-status--{$status} status-badge--{$status}"]) }}@if ($meaning) title="{{ $meaning }}"@endif>
    <span class="job-status-dot"></span>
    {{ $label }}
</span>
