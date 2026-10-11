<x-mailbox-layout>
    <x-slot:actions>
        <span class="text-base text-ink-soft">{{ __('mailbox.teacher_prompt') }}</span>
        <a href="{{ route('login') }}"
           class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-line-strong bg-surface px-5 text-base font-bold text-ink hover:bg-paper hover:text-ink">{{ __('mailbox.teacher_login') }}</a>
        <a href="{{ route('register') }}"
           class="inline-flex min-h-[44px] items-center rounded-lg bg-accent px-5 text-base font-bold text-white hover:bg-accent-hover hover:text-white">{{ __('mailbox.teacher_register') }}</a>
    </x-slot:actions>

    <div class="flex flex-1 items-center justify-center px-4 pb-16 pt-6">
        <x-card class="w-full max-w-[440px] space-y-5 !p-8 sm:!p-9">
            <x-flash />

            <div class="space-y-2">
                <h1 class="text-3xl leading-tight">{{ __('mailbox.login_title') }}</h1>
                <p class="text-[17px] leading-snug text-ink-soft">{{ __('mailbox.login_lead') }}</p>
            </div>

            <form method="POST" action="{{ route('mailbox.login') }}" class="space-y-5">
                @csrf

                <div class="space-y-1.5">
                    <x-input-label for="address" :value="__('mailbox.address')" />
                    <x-text-input id="address" name="address" type="text" class="font-mono text-base"
                                  :value="old('address')" autofocus required autocomplete="username"
                                  autocapitalize="none" autocorrect="off" spellcheck="false" />
                    <p class="text-[15px] text-ink-soft">{{ __('mailbox.address_hint') }}</p>
                    <x-input-error :messages="$errors->get('address')" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="pin" :value="__('mailbox.pin')" />
                    <x-text-input id="pin" name="pin" type="text" class="font-mono text-[22px] tracking-[2px]"
                                  required autocomplete="off" autocapitalize="characters" spellcheck="false" />
                </div>

                <x-captcha />

                <x-primary-button class="w-full">{{ __('mailbox.login_submit') }}</x-primary-button>

                <p class="text-[15px] leading-snug text-ink-soft">{{ __('mailbox.pin_note') }}</p>
            </form>
        </x-card>
    </div>
</x-mailbox-layout>
