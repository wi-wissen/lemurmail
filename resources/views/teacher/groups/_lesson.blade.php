@php
    $now = \App\Support\Clock::now();
    $running = $group->window()?->isActive($now) ?? false;
    $today = \Illuminate\Support\Carbon::createFromTimestamp($now, config('app.timezone'));
    // The last selectable day: its 23:59 must still lie within the PIN maximum (the server checks exactly that).
    $lastDate = \Illuminate\Support\Carbon::createFromTimestamp($now + config('lemurmail.max_pin_seconds'), config('app.timezone'))
        ->subDay()->toDateString();
@endphp

<div class="flex flex-col gap-6">
    @if ($errors->hasAny(['minutes', 'until', 'until_date', 'senders', 'addresses', 'lesson']))
        <x-alert type="error">
            @foreach (['minutes', 'until', 'until_date', 'senders', 'addresses', 'lesson'] as $key)
                @foreach ($errors->get($key) as $message)
                    <p>{{ $message }}</p>
                @endforeach
            @endforeach
        </x-alert>
    @endif

    @if ($running)
        @php
            $validUntil = \App\Support\TimeLabel::until($group->lesson_ends_at, $now);
            $left = $group->lesson_ends_at - $now;
            $daysLeft = (int) ceil($left / 86400);
            $initialRemaining = $left >= 86400
                ? __('lesson.remaining_days_'.($daysLeft === 1 ? 'one' : 'other'), ['count' => $daysLeft])
                : __('lesson.remaining', ['minutes' => (int) ceil($left / 60)]);
        @endphp
        <div
            x-data="lessonCountdown"
            data-ends-at="{{ $group->lesson_ends_at }}"
            data-server-now="{{ $now }}"
            data-remaining-template="{{ __('lesson.remaining', ['minutes' => ':minutes']) }}"
            data-remaining-days-one="{{ __('lesson.remaining_days_one', ['count' => ':count']) }}"
            data-remaining-days-other="{{ __('lesson.remaining_days_other', ['count' => ':count']) }}"
            class="flex flex-wrap items-center justify-between gap-4 rounded-lg border-[1.5px] border-accent bg-accent-soft p-5 sm:p-6"
        >
            <div class="flex flex-col gap-1">
                <span class="text-[19px] font-bold">{{ __('lesson.running_until', ['time' => $validUntil]) }}</span>
                <span class="text-base text-[#2F4A41]" x-text="text">{{ $initialRemaining }}</span>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <a href="{{ route('groups.beamer', $group) }}" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-accent px-5 text-base font-bold text-white hover:bg-accent-hover hover:text-white">{{ __('lesson.open_beamer') }}</a>
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
                    @foreach (['45', '90', 'until', 'date'] as $choice)
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

                <label class="flex flex-col gap-1.5 text-[15px] font-bold" x-show="choice === 'date'">
                    {{ __('lesson.until_date_label') }}
                    <x-text-input type="date" name="until_date" min="{{ $today->toDateString() }}" max="{{ $lastDate }}"
                        x-bind:disabled="choice !== 'date'" class="max-w-[12rem]" />
                </label>

                <p class="text-[15px] leading-relaxed text-ink-soft" x-show="choice === 'date'">{{ __('lesson.date_hint') }}</p>
                <p class="text-[15px] leading-relaxed text-ink-soft">{{ __('lesson.start_hint') }}</p>

                <div>
                    <x-primary-button>{{ __('lesson.start') }}</x-primary-button>
                </div>
            </form>
        </section>
    @endif
</div>
