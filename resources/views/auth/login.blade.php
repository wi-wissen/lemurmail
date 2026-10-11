<x-guest-layout>
    <x-card>
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-2">
                <h1 class="text-[30px] font-bold leading-tight">{{ __('auth.login_title') }}</h1>
                <p class="text-[17px] leading-snug text-ink-soft">{{ __('auth.login_intro') }}</p>
            </div>

            <x-auth-session-status :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('auth.email')" />
                    <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('auth.password_label')" />
                    <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <label for="remember_me" class="inline-flex items-center gap-3 text-base text-ink">
                    <input id="remember_me" type="checkbox" name="remember" class="h-5 w-5 rounded border-line-strong text-accent focus:ring-accent">
                    {{ __('auth.remember_me') }}
                </label>

                <x-primary-button class="w-full">{{ __('auth.login_submit') }}</x-primary-button>
            </form>

            <div class="flex flex-col gap-3 border-t border-line pt-5 text-base">
                <a href="{{ route('password.request') }}" class="text-accent underline hover:text-accent-hover">{{ __('auth.forgot_password_link') }}</a>
                <a href="{{ route('register') }}" class="text-accent underline hover:text-accent-hover">{{ __('auth.register_link') }}</a>
            </div>
        </div>
    </x-card>
</x-guest-layout>
