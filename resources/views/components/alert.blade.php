@props(['type' => 'success', 'title' => null])
@php
    $styles = [
        'success' => 'bg-accent-soft border-accent text-ink',
        'warn' => 'bg-warn-soft border-warn text-ink',
        'error' => 'bg-danger-soft border-danger text-ink',
    ][$type] ?? 'bg-accent-soft border-accent text-ink';
@endphp
<div {{ $attributes->merge(['class' => "rounded-lg border-[1.5px] px-5 py-4 text-base leading-snug $styles"]) }}>
    @if ($title)<div class="mb-1 text-[17px] font-bold">{{ $title }}</div>@endif
    {{ $slot }}
</div>
