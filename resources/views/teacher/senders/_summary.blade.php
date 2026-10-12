<section class="flex flex-col gap-3 rounded-lg border border-line bg-surface p-6">
    <h2 class="text-xl font-bold">{{ __('teacher.senders.title') }}</h2>

    @if ($rules->isEmpty())
        <p class="text-[15px] text-ink-soft">{{ __('teacher.senders.empty') }}</p>
        <p class="text-[15px] leading-snug text-ink-soft">{{ __('teacher.senders.hint') }}</p>
    @else
        <ul class="flex flex-wrap gap-2 font-mono text-[14px]">
            @foreach ($rules as $rule)
                <li class="rounded-lg bg-accent-soft px-2.5 py-1.5">{{ $rule->pattern }}</li>
            @endforeach
        </ul>
        <p class="text-[15px] leading-snug text-ink-soft">{{ __('teacher.senders.scope_all') }}</p>
    @endif

    <a href="{{ route('senders.index') }}" class="text-base font-bold">{{ __('teacher.senders.edit_default') }}</a>
</section>
