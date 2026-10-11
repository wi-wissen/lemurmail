<x-guest-layout>
    <x-card>
        <div class="flex flex-col gap-5">
            <x-alert :type="$status === 'pending' ? 'warn' : 'error'" :title="__('account.status_'.$status.'_title')">
                {{ __('account.status_'.$status.'_text') }}
            </x-alert>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-secondary-button type="submit" class="w-full">{{ __('common.logout') }}</x-secondary-button>
            </form>
        </div>
    </x-card>
</x-guest-layout>
