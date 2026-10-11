@php
    $now = \App\Support\Clock::now();
    $running = $group->window()?->isActive($now) ?? false;
@endphp

<div class="flex flex-col gap-6">
    @if ($errors->hasAny(['minutes', 'until', 'senders', 'addresses', 'lesson']))
        <x-alert type="error">
            @foreach (['minutes', 'until', 'senders', 'addresses', 'lesson'] as $key)
                @foreach ($errors->get($key) as $message)
                    <p>{{ $message }}</p>
                @endforeach
            @endforeach
        </x-alert>
    @endif

    @if ($running)
        @php
            $validUntil = \Illuminate\Support\Carbon::createFromTimestamp($group->lesson_ends_at, config('app.timezone'))->format('H:i');
            $minutesLeft = (int) ceil(($group->lesson_ends_at - $now) / 60);
        @endphp
        <div
            x-data="lessonCountdown"
            data-ends-at="{{ $group->lesson_ends_at }}"
            data-server-now="{{ $now }}"
            data-remaining-template="{{ __('lesson.remaining', ['minutes' => ':minutes']) }}"
            class="flex flex-wrap items-center justify-between gap-4 rounded-lg border-[1.5px] border-accent bg-accent-soft p-5 sm:p-6"
        >
            <div class="flex flex-col gap-1">
                <span class="text-[19px] font-bold">{{ __('lesson.running_until', ['time' => $validUntil]) }}</span>
                <span class="text-base text-[#2F4A41]" x-text="text">{{ __('lesson.remaining', ['minutes' => $minutesLeft]) }}</span>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <a href="{{ route('groups.beamer', $group) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-accent px-5 text-base font-bold text-white hover:bg-accent-hover">{{ __('lesson.open_beamer') }}</a>
                <form method="POST" action="{{ route('groups.lesson.extend', $group) }}">
                    @csrf
                    <x-secondary-button type="submit">{{ __('lesson.extend') }}</x-secondary-button>
                </form>
                <form method="POST" action="{{ route('groups.lesson.end', $group) }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border-[1.5px] border-warn bg-surface px-5 text-base font-bold text-warn-ink hover:bg-warn-soft">{{ __('lesson.end') }}</button>
                </form>
                <form method="POST" action="{{ route('groups.pins.regenerate', $group) }}">
                    @csrf
                    <x-secondary-button type="submit">{{ __('lesson.regenerate_all') }}</x-secondary-button>
                </form>
            </div>
        </div>
    @else
        <section class="flex flex-col gap-4 rounded-lg border border-line bg-surface p-6 sm:p-8">
            <h2 class="text-xl font-bold">{{ __('lesson.duration_heading') }}</h2>

            <form method="POST" action="{{ route('groups.lesson.start', $group) }}" x-data="lessonForm" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="mode" :value="mode">
                <input type="hidden" name="minutes" :value="minutes">

                <div class="flex flex-wrap gap-2" role="radiogroup">
                    @foreach (['45', '90', 'until'] as $choice)
                        <label class="cursor-pointer">
                            <input type="radio" value="{{ $choice }}" x-model="choice" class="peer sr-only">
                            <span class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-line-strong bg-surface px-4 text-base font-bold text-ink peer-checked:border-ink peer-checked:bg-ink peer-checked:text-white peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-accent">{{ __('lesson.duration_'.$choice) }}</span>
                        </label>
                    @endforeach
                </div>

                <label class="flex flex-col gap-1.5 text-[15px] font-bold" x-show="choice === 'until'">
                    {{ __('lesson.until_label') }}
                    <x-text-input type="time" name="until" x-bind:disabled="choice !== 'until'" class="max-w-[12rem]" />
                </label>

                <p class="text-[15px] leading-relaxed text-ink-soft">{{ __('lesson.start_hint') }}</p>

                <div>
                    <x-primary-button>{{ __('lesson.start') }}</x-primary-button>
                </div>
            </form>
        </section>
    @endif
</div>
