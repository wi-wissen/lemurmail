<x-guest-layout>
    <x-auth-card role="teacher" :title="__('auth.verify_title')" :intro="__('auth.verify_intro')">
        @if (session('status') == 'verification-link-sent')
            <x-alert type="success">{{ __('auth.verify_sent') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">{{ __('auth.verify_resend') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-line pt-5">
            @csrf
            <button type="submit" class="text-base text-accent underline hover:text-accent-hover">{{ __('common.logout') }}</button>
        </form>
    </x-auth-card>
</x-guest-layout>
