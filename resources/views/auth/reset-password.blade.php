<x-guest-layout>
    <x-auth-card role="teacher" :title="__('auth.reset_title')">
        <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-5">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-input-label for="email" :value="__('auth.email')" />
                <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('auth.new_password')" />
                <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="new-password" />
                <p class="mt-1.5 text-[15px] text-ink-soft">{{ __('auth.password_hint') }}</p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('auth.password_confirmation')" />
                <x-text-input id="password_confirmation" class="mt-1.5" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <x-primary-button class="w-full">{{ __('auth.reset_submit') }}</x-primary-button>
        </form>
    </x-auth-card>
</x-guest-layout>
