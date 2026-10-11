<x-guest-layout>
    <x-card>
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-2">
                <h1 class="text-[30px] font-bold leading-tight">{{ __('auth.verify_title') }}</h1>
                <p class="text-[17px] leading-snug text-ink-soft">{{ __('auth.verify_intro') }}</p>
            </div>

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
        </div>
    </x-card>
</x-guest-layout>
