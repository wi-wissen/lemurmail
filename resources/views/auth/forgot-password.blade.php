<x-guest-layout>
    <x-auth-card role="teacher" :title="__('auth.forgot_title')" :intro="__('auth.forgot_intro')" switch="student">
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
    </x-auth-card>
</x-guest-layout>
