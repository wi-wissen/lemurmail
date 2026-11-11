<x-guest-layout>
    <x-auth-card role="student" :title="__('mailbox.login_title')" :intro="__('mailbox.login_lead')" switch="teacher">
        <x-flash />

        <form method="POST" action="{{ route('mailbox.login') }}" class="flex flex-col gap-5">
            @csrf

            <div class="flex flex-col gap-1.5">
                <x-input-label for="address" :value="__('mailbox.address')" />
                <x-text-input id="address" name="address" type="text" class="font-mono text-base"
                              :value="old('address')" autofocus required autocomplete="username"
                              autocapitalize="none" autocorrect="off" spellcheck="false" aria-describedby="address-hint" />
                <p id="address-hint" class="text-[15px] text-ink-soft">{{ __('mailbox.address_hint') }}</p>
                <x-input-error :messages="$errors->get('address')" />
            </div>

            <div class="flex flex-col gap-1.5">
                <x-input-label for="pin" :value="__('mailbox.pin')" />
                <x-text-input id="pin" name="pin" type="text" class="font-mono text-[22px] tracking-[2px]"
                              required autocomplete="off" autocapitalize="characters" spellcheck="false" />
            </div>

            <x-captcha />

            <x-primary-button class="w-full">{{ __('mailbox.login_submit') }}</x-primary-button>

            <p class="text-[15px] leading-snug text-ink-soft">{{ __('mailbox.pin_note') }}</p>
        </form>
    </x-auth-card>
</x-guest-layout>
