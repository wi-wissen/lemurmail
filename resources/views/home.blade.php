{{-- Start page: explains Lemur Mail in one sentence and sends students and teachers their own way. --}}
<x-guest-layout wide>
    <x-slot:headerLink>
        @if (config('lemurmail.about_url'))
            <a href="{{ config('lemurmail.about_url') }}" rel="noopener">{{ __('home.about') }}</a>
        @endif
    </x-slot:headerLink>

    <div class="flex flex-col gap-10">
        <x-flash />

        <div class="flex flex-wrap items-center gap-x-8 gap-y-6">
            <x-mascot class="h-24 w-24 shrink-0 sm:h-28 sm:w-28" />
            <div class="flex min-w-0 flex-[1_1_420px] flex-col gap-2.5">
                <h1 class="text-[34px] font-bold leading-tight tracking-tight sm:text-[44px]">{{ __('home.title') }}</h1>
                <p class="max-w-[40em] text-lg text-ink-soft sm:text-xl">{{ __('home.lead') }}</p>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <section aria-labelledby="home-student" class="flex flex-col gap-4 rounded-xl border-2 border-accent bg-surface p-7 sm:p-8">
                <div class="flex items-center gap-3.5">
                    <span class="grid h-[52px] w-[52px] place-items-center rounded-xl bg-accent-soft text-accent-hover"><x-icons.inbox class="h-7 w-7" /></span>
                    <span class="text-[15px] font-bold uppercase tracking-wide text-accent-hover">{{ __('common.role_student') }}</span>
                </div>
                <h2 id="home-student" class="text-[30px] font-bold leading-tight">{{ __('home.student_title') }}</h2>
                <p class="flex-1 text-[17px] text-ink-soft">{{ __('home.student_text') }}</p>
                <a href="{{ route('mailbox.login.form') }}"
                   class="inline-flex min-h-[52px] items-center justify-center gap-2.5 rounded-lg bg-accent px-6 text-lg font-bold text-white hover:bg-accent-hover hover:text-white">
                    {{ __('home.student_action') }} <span aria-hidden="true">→</span>
                </a>
            </section>

            <section aria-labelledby="home-teacher" class="flex flex-col gap-4 rounded-xl border border-line bg-surface p-7 sm:p-8">
                <div class="flex items-center gap-3.5">
                    <span class="grid h-[52px] w-[52px] place-items-center rounded-xl border border-line bg-paper text-ink"><x-icons.users class="h-7 w-7" /></span>
                    <span class="text-[15px] font-bold uppercase tracking-wide text-ink-soft">{{ __('common.role_teacher') }}</span>
                </div>
                <h2 id="home-teacher" class="text-[30px] font-bold leading-tight">{{ __('home.teacher_title') }}</h2>
                <p class="flex-1 text-[17px] text-ink-soft">{{ __('home.teacher_text') }}</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('login') }}"
                       class="inline-flex min-h-[52px] flex-[1_1_160px] items-center justify-center rounded-lg border-[1.5px] border-line-strong bg-surface px-6 text-lg font-bold text-ink hover:bg-paper hover:text-ink">{{ __('home.teacher_login') }}</a>
                    <a href="{{ route('register') }}"
                       class="inline-flex min-h-[52px] flex-[1_1_160px] items-center justify-center rounded-lg border-[1.5px] border-line-strong bg-surface px-6 text-lg font-bold text-ink hover:bg-paper hover:text-ink">{{ __('home.teacher_register') }}</a>
                </div>
            </section>
        </div>

        <section aria-labelledby="home-how" class="flex flex-col gap-4 border-t border-line pt-8">
            <h2 id="home-how" class="text-[22px] font-bold">{{ __('home.how_title') }}</h2>
            <ol class="grid gap-x-8 gap-y-4 md:grid-cols-3">
                @foreach ([1, 2, 3] as $step)
                    <li class="flex items-start gap-3.5">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-accent-soft font-bold text-accent-hover">{{ $step }}</span>
                        <span class="text-[17px] text-ink-soft"><strong class="text-ink">{{ __('home.step'.$step.'_title') }}</strong>: {{ __('home.step'.$step.'_text') }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</x-guest-layout>
