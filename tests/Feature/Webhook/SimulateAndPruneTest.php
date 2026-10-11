<?php

use App\Models\Address;
use App\Models\Message;
use App\Support\Clock;
use Illuminate\Console\Scheduling\Schedule;

it('simulates a correctly signed inbound mail', function () {
    addressWithWhitelist('klasse7a-03', '@example-dienst.example');

    $this->artisan('mail:simulate', ['recipient' => 'klasse7a-03@tmp-mail.example.org', '--subject' => 'Hallo Test'])->assertSuccessful();

    $m = Message::sole();
    expect($m->subject)->toBe('Hallo Test')->and($m->from_email)->toBe('noreply@example-dienst.example');
});

it('the simulation honours the whitelist like a real mail', function () {
    addressWithWhitelist('klasse7a-03', '@erlaubt.example');

    $this->artisan('mail:simulate', ['recipient' => 'klasse7a-03@tmp-mail.example.org'])->assertSuccessful();

    expect(Message::count())->toBe(0);
});

it('the simulation accepts a custom sender', function () {
    addressWithWhitelist('klasse7a-03', '@erlaubt.example');

    $this->artisan('mail:simulate', ['recipient' => 'klasse7a-03@tmp-mail.example.org', '--from' => 'hallo@erlaubt.example'])->assertSuccessful();

    expect(Message::sole()->from_email)->toBe('hallo@erlaubt.example');
});

it('prunes messages older than the retention', function () {
    $address = Address::factory()->create();
    $now = Clock::now();
    $old = Message::factory()->create(['address_id' => $address->id, 'received_at' => $now - 7 * 86400 - 60]);
    $fresh = Message::factory()->create(['address_id' => $address->id, 'received_at' => $now - 6 * 86400]);

    $this->artisan('mail:prune')->assertSuccessful();

    expect(Message::find($old->id))->toBeNull()->and(Message::find($fresh->id))->not->toBeNull();
});

it('never keeps mail longer than seven days, whatever the config says', function () {
    config(['lemurmail.retention_days' => 30]);
    $address = Address::factory()->create();
    $old = Message::factory()->create(['address_id' => $address->id, 'received_at' => Clock::now() - 8 * 86400]);

    $this->artisan('mail:prune')->assertSuccessful();

    expect(Message::find($old->id))->toBeNull();
});

it('drops the plaintext of expired PINs but keeps live ones', function () {
    $now = Clock::now();
    $expired = Address::factory()->withPin('K7M4QX9R', $now - 10)->create();
    $live = Address::factory()->withPin('T3WD8HNP', $now + 600)->create();

    $this->artisan('mail:prune')->assertSuccessful();

    expect($expired->fresh()->pin_encrypted)->toBeNull()->and($live->fresh()->pin_encrypted)->toBe('T3WD8HNP');
});

it('runs hourly in the scheduler', function () {
    $events = collect(app(Schedule::class)->events())->filter(fn ($e) => str_contains($e->command ?? '', 'mail:prune'));
    expect($events)->toHaveCount(1)->and($events->first()->expression)->toBe('0 * * * *');
});
