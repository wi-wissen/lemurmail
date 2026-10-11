<section class="flex flex-col gap-5">
    <header class="flex flex-col gap-2">
        <h2 class="text-2xl font-bold">{{ __('account.profile_email_title') }}</h2>
        <p class="text-[17px] leading-snug text-ink-soft">{{ __('account.profile_email_text') }}</p>
    </header>

    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>
    @endif

    <form method="post" action="{{ route('profile.update') }}" class="flex flex-col gap-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="email" :value="__('auth.email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1.5" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 flex flex-col gap-2 text-[17px]">
                    <p>{{ __('account.profile_unverified') }}</p>
                    <button form="send-verification" class="w-fit text-accent underline hover:text-accent-hover">{{ __('account.profile_resend') }}</button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="font-bold text-accent">{{ __('account.profile_resent') }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('common.save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p class="text-[17px] text-ink-soft">{{ __('account.profile_saved') }}</p>
            @endif
        </div>
    </form>
</section>
