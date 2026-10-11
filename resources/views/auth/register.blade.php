<x-guest-layout>
    <x-card>
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-2">
                <h1 class="text-[30px] font-bold leading-tight">{{ __('auth.register_title') }}</h1>
                <p class="text-[17px] leading-snug text-ink-soft">{{ __('auth.register_intro') }}</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-5">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('auth.email')" />
                    <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@schule.example.org" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('auth.password_label')" />
                    <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="new-password" aria-describedby="password-hint" />
                    <p id="password-hint" class="mt-1.5 text-[15px] text-ink-soft">{{ __('auth.password_hint') }}</p>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <x-captcha />

                <x-primary-button class="w-full">{{ __('auth.register_submit') }}</x-primary-button>
            </form>

            <div class="flex flex-col gap-3 border-t border-line pt-5 text-[15px] leading-snug text-ink-soft">
                <p>{{ __('auth.hint_approved') }}</p>
                <p>{{ __('auth.hint_unknown') }}</p>
            </div>

            <a href="{{ route('login') }}" class="text-base text-accent underline hover:text-accent-hover">{{ __('auth.register_login_link') }}</a>
        </div>
    </x-card>
</x-guest-layout>
