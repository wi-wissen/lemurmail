<x-beamer-layout :title="$group->name">
    <main
        x-data="beamer"
        data-ends-at="{{ $endsAt }}"
        data-server-now="{{ $now }}"
        x-bind:style="'--scale: ' + scale / 100"
        class="flex min-h-screen flex-col gap-5 px-5 py-6 sm:px-14 sm:pb-6 sm:pt-8"
    >
        <header class="flex flex-wrap items-end justify-between gap-8">
            <div class="flex flex-col gap-1.5">
                <span class="text-beamer-muted" style="font-size: calc(1.5rem * var(--scale, 1))">{{ __('lesson.beamer_intro', ['domain' => $domain]) }}</span>
                <h1 class="font-bold leading-tight" style="font-size: calc(3.75rem * var(--scale, 1))">{{ $group->name }}</h1>
            </div>
            <div class="flex flex-wrap items-baseline gap-6">
                <div class="flex flex-col items-end">
                    <span class="text-beamer-muted" style="font-size: calc(1.25rem * var(--scale, 1))">{{ __('lesson.valid_until') }}</span>
                    <span class="font-mono font-bold leading-none text-beamer-accent" style="font-size: calc(4rem * var(--scale, 1))">{{ $validUntil }}</span>
                </div>
                <span class="font-mono text-beamer-warn" style="font-size: calc(1.875rem * var(--scale, 1))">
                    {{ __('lesson.beamer_remaining') }} <span x-text="remaining"></span>
                </span>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-x-6 gap-y-2 md:grid-cols-2">
            @forelse ($pins as $row)
                <div class="flex items-center justify-between gap-4 rounded-lg bg-beamer-row px-7 py-2">
                    <span class="font-mono break-all" style="font-size: calc(2rem * var(--scale, 1))">{{ $row['local_part'] }}</span>
                    <span x-show="!hidePins" class="font-mono font-bold tracking-[0.12em] text-beamer-accent whitespace-nowrap" style="font-size: calc(2.625rem * var(--scale, 1))">{{ $row['pin'] }}</span>
                    <span x-show="hidePins" class="font-mono font-bold text-beamer-muted whitespace-nowrap" style="font-size: calc(2.625rem * var(--scale, 1))" aria-hidden="true">••••&nbsp;••••</span>
                </div>
            @empty
                <p class="text-beamer-muted md:col-span-2" style="font-size: calc(1.5rem * var(--scale, 1))">{{ __('lesson.beamer_empty') }}</p>
            @endforelse
        </div>

        <footer class="mt-auto flex flex-wrap items-center justify-between gap-4">
            <span class="text-[17px] text-beamer-muted">{{ __('lesson.beamer_footer') }}</span>
            <div class="flex flex-wrap gap-2.5">
                <button type="button" class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-beamer-muted px-4 text-base font-bold text-beamer-text hover:bg-beamer-row" @click="changeScale(-1)">{{ __('lesson.font_smaller') }}</button>
                <button type="button" class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-beamer-muted px-4 text-base font-bold text-beamer-text hover:bg-beamer-row" @click="changeScale(1)">{{ __('lesson.font_larger') }}</button>
                <button type="button" class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-beamer-muted px-4 text-base font-bold text-beamer-text hover:bg-beamer-row" @click="togglePins()">
                    <span x-show="!hidePins">{{ __('lesson.hide_pins') }}</span>
                    <span x-show="hidePins">{{ __('lesson.show_pins') }}</span>
                </button>
                <form method="POST" action="{{ route('groups.lesson.extend', $group) }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-beamer-muted px-4 text-base font-bold text-beamer-text hover:bg-beamer-row">{{ __('lesson.extend') }}</button>
                </form>
                <button type="button" class="inline-flex min-h-[44px] items-center rounded-lg bg-beamer-accent px-4 text-base font-bold text-beamer-bg" @click="toggleFullscreen()">{{ __('lesson.fullscreen') }}</button>
            </div>
        </footer>
    </main>
</x-beamer-layout>
