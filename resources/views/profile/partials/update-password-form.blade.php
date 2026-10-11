<section class="flex flex-col gap-5">
    <header class="flex flex-col gap-2">
        <h2 class="text-2xl font-bold">{{ __('account.profile_password_title') }}</h2>
        <p class="text-[17px] leading-snug text-ink-soft">{{ __('account.profile_password_text') }}</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('auth.current_password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1.5" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('auth.new_password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1.5" autocomplete="new-password" />
            <p class="mt-1.5 text-[15px] text-ink-soft">{{ __('auth.password_hint') }}</p>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('auth.password_confirmation')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1.5" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('common.save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p class="text-[17px] text-ink-soft">{{ __('account.profile_saved') }}</p>
            @endif
        </div>
    </form>
</section>
