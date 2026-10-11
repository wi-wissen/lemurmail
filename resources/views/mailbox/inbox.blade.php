@php
    $validUntil = \App\Support\TimeLabel::until($address->pin_expires_at, $now);
    $left = max(0, $address->pin_expires_at - $now);
    $daysLeft = (int) ceil($left / 86400);
    $countdown = [
        'expiresAt' => $address->pin_expires_at,
        'serverNow' => $now,
        'homeUrl' => route('home'),
        'template' => __('mailbox.pin_valid_until', ['time' => $validUntil, 'minutes' => ':minutes']),
        'daysOne' => __('mailbox.pin_valid_until_days_one', ['time' => $validUntil, 'count' => ':count']),
        'daysOther' => __('mailbox.pin_valid_until_days_other', ['time' => $validUntil, 'count' => ':count']),
    ];
    $initialLabel = $left >= 86400
        ? __('mailbox.pin_valid_until_days_'.($daysLeft === 1 ? 'one' : 'other'), ['time' => $validUntil, 'count' => $daysLeft])
        : __('mailbox.pin_valid_until', ['time' => $validUntil, 'minutes' => (int) ceil($left / 60)]);
@endphp

<x-mailbox-layout>
    <x-slot:meta>
        <span class="break-all rounded-lg bg-accent-soft px-3 py-1.5 font-mono text-base">{{ $address->email() }}</span>
        <span class="rounded-lg bg-warn-soft px-3 py-1.5 text-base font-bold text-warn-ink"
              x-data="pinCountdown(@js($countdown))" x-text="label">{{ $initialLabel }}</span>
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
