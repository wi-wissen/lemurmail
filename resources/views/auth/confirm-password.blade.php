<x-guest-layout>
    <x-card>
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-2">
                <h1 class="text-[30px] font-bold leading-tight">{{ __('auth.confirm_title') }}</h1>
                <p class="text-[17px] leading-snug text-ink-soft">{{ __('auth.confirm_intro') }}</p>
            </div>

            <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-5">
                @csrf

                <div>
                    <x-input-label for="password" :value="__('auth.password_label')" />
                    <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <x-primary-button class="w-full">{{ __('auth.confirm_submit') }}</x-primary-button>
            </form>
        </div>
    </x-card>
</x-guest-layout>
