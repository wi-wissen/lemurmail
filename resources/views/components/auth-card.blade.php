{{--
    Card of every sign-in page (mailbox login and teacher account pages), so they all look the same.
    role: "student" | "teacher" (badge). switch: "teacher" | "student" | null, shows a link to the other login
    for people who landed on the wrong page.
--}}
@props(['role', 'title', 'intro' => null, 'switch' => null])
<section aria-labelledby="auth-card-title" {{ $attributes->merge(['class' => 'flex w-full flex-col gap-5 rounded-xl border border-line bg-surface p-7 sm:p-9']) }}>
    <x-role-badge :role="$role" />

    <div class="flex flex-col gap-1.5">
        <h1 id="auth-card-title" class="text-[30px] font-bold leading-tight">{{ $title }}</h1>
        @if ($intro)
            <p class="text-[17px] leading-snug text-ink-soft">{{ $intro }}</p>
        @endif
    </div>

    {{ $slot }}

    @if ($switch)
        <div class="flex flex-col gap-1 border-t border-line pt-5 text-base">
            <span class="text-ink-soft">{{ __('common.switch_to_'.$switch.'_prompt') }}</span>
            <a href="{{ $switch === 'teacher' ? route('login') : route('mailbox.login.form') }}" class="font-bold">
                {{ __('common.switch_to_'.$switch) }} <span aria-hidden="true">→</span>
            </a>
        </div>
    @endif
</section>
