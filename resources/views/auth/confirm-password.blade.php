<x-guest-layout>
    <x-auth-card role="teacher" :title="__('auth.confirm_title')" :intro="__('auth.confirm_intro')">
        <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-5">
            @csrf

            <div>
                <x-input-label for="password" :value="__('auth.password_label')" />
                <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <x-primary-button class="w-full">{{ __('auth.confirm_submit') }}</x-primary-button>
        </form>
    </x-auth-card>
</x-guest-layout>
