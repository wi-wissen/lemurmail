@props(['href', 'active' => false])
<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    {{ $attributes->class(['pb-1 border-b-[3px] font-bold text-ink hover:text-ink border-accent' => $active, 'pb-1 border-b-[3px] border-transparent text-ink-soft hover:text-ink' => ! $active]) }}>{{ $slot }}</a>
