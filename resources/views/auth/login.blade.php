<x-guest-layout>
    <x-auth-card role="teacher" :title="__('auth.login_title')" :intro="__('auth.login_intro')" switch="student">
        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
            @csrf

            <div>
                <x-input-label for="email" :value="__('auth.email')" />
                <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                    <x-input-label for="password" :value="__('auth.password_label')" />
                    <a href="{{ route('password.request') }}" class="text-[15px] underline">{{ __('auth.forgot_password_link') }}</a>
                </div>
                <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <label for="remember_me" class="inline-flex items-center gap-3 text-base text-ink">
                <input id="remember_me" type="checkbox" name="remember" class="h-5 w-5 rounded border-line-strong text-accent focus:ring-accent">
                {{ __('auth.remember_me') }}
            </label>

            <x-captcha />

            <x-primary-button class="w-full">{{ __('auth.login_submit') }}</x-primary-button>

            <a href="{{ route('register') }}" class="text-base underline">{{ __('auth.register_link') }}</a>
        </form>
    </x-auth-card>
</x-guest-layout>
