<div class="space-y-2">
    <h1 class="text-[28px] leading-tight">{{ __('mailbox.secret_form_title') }}</h1>
    <span class="inline-block break-all rounded-lg bg-accent-soft px-2.5 py-1 font-mono text-[15px]">{{ $address->email() }}</span>
</div>

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="space-y-1.5">
        <x-input-label for="secret" :value="__('mailbox.secret_label')" />
        <x-text-input id="secret" name="secret" type="text" class="font-mono text-base" maxlength="500"
                      autocomplete="off" autocapitalize="none" spellcheck="false" />
        <p class="text-[15px] leading-snug text-ink-soft">{{ __('mailbox.secret_edit_hint') }}</p>
        <x-input-error :messages="$errors->get('secret')" />
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <x-primary-button>{{ __('common.save') }}</x-primary-button>
        <a href="{{ $cancelUrl }}" class="text-[17px] text-ink underline">{{ __('common.cancel') }}</a>
    </div>
</form>
