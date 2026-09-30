@props([
    'variant' => 'full', // full = horse + "South West" + "Equine Services"; mark = without the strapline
    'tone' => 'gold',    // gold (dark backgrounds), gold-deep / navy (light backgrounds), white
    'width' => 180,
])

@php
    $ratio = $variant === 'mark' ? 419 / 587 : 456 / 535;
@endphp

<img
    {{ $attributes->merge(['class' => 'brand-logo']) }}
    src="{{ asset("images/brand/logo-{$variant}-{$tone}.png") }}"
    alt="South West Equine Services"
    width="{{ $width }}"
    height="{{ (int) round($width * $ratio) }}"
>
