<section class="flex flex-col gap-5">
    <header class="flex flex-col gap-2">
        <h2 class="text-2xl font-bold">{{ __('account.profile_delete_title') }}</h2>
        <p class="text-[17px] leading-snug text-ink-soft">{{ __('account.profile_delete_text') }}</p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('account.profile_delete_button') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="flex flex-col gap-5 p-6">
            @csrf
            @method('delete')

            <h2 class="text-2xl font-bold">{{ __('account.profile_delete_confirm_title') }}</h2>

            <p class="text-[17px] leading-snug text-ink-soft">{{ __('account.profile_delete_confirm_text') }}</p>

            <div>
                <x-input-label for="delete_password" :value="__('auth.password_label')" class="sr-only" />
                <x-text-input id="delete_password" name="password" type="password" class="mt-1.5" placeholder="{{ __('auth.password_label') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('common.cancel') }}</x-secondary-button>
                <x-danger-button>{{ __('account.profile_delete_button') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
