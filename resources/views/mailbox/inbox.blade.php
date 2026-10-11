@php
    $countdown = [
        'expiresAt' => $address->pin_expires_at,
        'serverNow' => $now,
        'homeUrl' => route('home'),
        'template' => __('mailbox.pin_valid_until', ['time' => $validUntil, 'minutes' => ':minutes']),
    ];
@endphp

<x-mailbox-layout>
    <x-slot:meta>
        <span class="break-all rounded-lg bg-accent-soft px-3 py-1.5 font-mono text-base">{{ $address->email() }}</span>
        <span class="rounded-lg bg-warn-soft px-3 py-1.5 text-base font-bold text-warn-ink"
              x-data="pinCountdown(@js($countdown))" x-text="label">{{ __('mailbox.pin_valid_until', ['time' => $validUntil, 'minutes' => $minutesLeft]) }}</span>
    </x-slot:meta>

    <x-slot:actions>
        <form method="POST" action="{{ route('mailbox.logout') }}">
            @csrf
            <x-secondary-button type="submit">{{ __('common.logout') }}</x-secondary-button>
        </form>
    </x-slot:actions>

    @include('mailbox._inbox', [
        'inbox' => [
            'messagesUrl' => route('mailbox.messages'),
            'secretUrl' => route('mailbox.secret.show'),
            'homeUrl' => route('home'),
        ],
        'secretEditUrl' => route('mailbox.secret.edit'),
    ])
</x-mailbox-layout>
