<x-guest-layout>
    <x-card>
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-2">
                <h1 class="text-[30px] font-bold leading-tight">{{ __('auth.forgot_title') }}</h1>
                <p class="text-[17px] leading-snug text-ink-soft">{{ __('auth.forgot_intro') }}</p>
            </div>

            <x-auth-session-status :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('auth.email')" />
                    <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <x-captcha />

                <x-primary-button class="w-full">{{ __('auth.forgot_submit') }}</x-primary-button>
            </form>

            <a href="{{ route('login') }}" class="border-t border-line pt-5 text-base text-accent underline hover:text-accent-hover">{{ __('common.back') }}</a>
        </div>
    </x-card>
</x-guest-layout>
