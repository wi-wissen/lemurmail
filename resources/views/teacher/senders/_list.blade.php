<section class="flex flex-col gap-4 rounded-lg border border-line bg-surface p-6">
    <h2 class="text-xl font-bold">{{ __('teacher.senders.title') }}</h2>

    @if ($rules->isEmpty())
        <p class="text-[15px] text-ink-soft">{{ __('teacher.senders.empty') }}</p>
    @else
        <ul class="flex flex-wrap gap-2 font-mono text-[14px]">
            @foreach ($rules as $rule)
                <li class="flex items-center gap-2 rounded-lg bg-accent-soft py-1.5 pl-2.5 pr-1.5">
                    <span>{{ $rule->pattern }}</span>
                    <form method="POST" action="{{ route('senders.destroy', $rule) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" aria-label="{{ __('teacher.senders.remove_label', ['pattern' => $rule->pattern]) }}"
                            class="rounded px-1.5 font-sans text-[14px] font-bold text-ink-soft underline hover:text-danger focus-visible:text-danger">{{ __('teacher.senders.remove') }}</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('senders.store') }}" class="flex flex-col gap-2">
        @csrf
        <label for="sender-pattern" class="text-base font-bold">{{ __('teacher.senders.add_label') }}</label>
        <div class="flex gap-2">
            <x-text-input id="sender-pattern" name="pattern" type="text" class="min-w-0 flex-1 font-mono text-[15px]"
                value="{{ old('pattern') }}" placeholder="{{ __('teacher.senders.placeholder') }}" required />
            <x-primary-button class="min-h-[44px] px-4 text-base">{{ __('teacher.senders.add') }}</x-primary-button>
        </div>
        <x-input-error :messages="$errors->get('pattern')" />
    </form>

    <p class="text-[15px] leading-snug text-ink-soft">{{ __('teacher.senders.hint') }}</p>
</section>
