@props(['status'])

@php
    $config = config("job_statuses.{$status}");
    $label = $config['label'] ?? \Illuminate\Support\Str::headline((string) $status);
    $meaning = $config['meaning'] ?? null;
    $modifier = $config ? "status-badge--{$status}" : 'badge-muted';
@endphp

<span {{ $attributes->merge(['class' => "badge status-badge {$modifier}"]) }}@if ($meaning) title="{{ $meaning }}"@endif>{{ $label }}</span>
