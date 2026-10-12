<x-app-layout>
    <div class="flex flex-col gap-6">
        <header class="min-w-0 space-y-2">
            <a href="{{ $group ? route('groups.show', $group) : route('groups.index') }}" class="text-base font-bold text-accent underline">
                {{ $group ? __('teacher.inbox.back_group') : __('teacher.inbox.back_groups') }}
            </a>
            <div class="flex min-w-0 items-start gap-3">
                <h1 class="min-w-0 break-all font-mono text-2xl leading-tight">{{ $address->email() }}</h1>
                <x-copy-button :text="$address->email()" class="mt-0.5" />
            </div>
        </header>

        <div class="flex min-h-[520px] flex-col overflow-hidden rounded-lg border border-line bg-surface">
            @include('mailbox._inbox', [
                'inbox' => [
                    'messagesUrl' => route('addresses.messages', $address),
                    'secretUrl' => route('addresses.secret.show', $address),
                    'homeUrl' => route('login'),
                ],
                'secretEditUrl' => route('addresses.secret.edit', $address),
            ])
        </div>
    </div>
</x-app-layout>
