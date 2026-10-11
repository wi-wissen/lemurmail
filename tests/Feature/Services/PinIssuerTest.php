<?php

use App\Models\Address;
use App\Services\PinIssuer;
use Illuminate\Support\Facades\Hash;

it('issues a PIN, stores hash and encrypted plaintext', function () {
    $address = Address::factory()->create();
    $pin = app(PinIssuer::class)->issue($address, 5000);

    $address->refresh();
    expect($pin)->toHaveLength(8)
        ->and(Hash::check($pin, $address->pin_hash))->toBeTrue()
        ->and($address->pin_encrypted)->toBe($pin)
        ->and($address->pin_expires_at)->toBe(5000);
    expect(DB::table('addresses')->where('id', $address->id)->value('pin_encrypted'))->not->toBe($pin);
});

it('revokes a PIN', function () {
    $address = Address::factory()->withPin('K7M4QX9R', 9000)->create();
    app(PinIssuer::class)->revoke($address, 100);

    $address->refresh();
    expect($address->pinIsValid(100))->toBeFalse()->and($address->pin_encrypted)->toBeNull();
    expect($address->pinIsValid(99))->toBeTrue();
});

it('moves the expiry only', function () {
    $address = Address::factory()->withPin('K7M4QX9R', 9000)->create();
    $hash = $address->pin_hash;
    app(PinIssuer::class)->setExpiry($address, 12000);

    $address->refresh();
    expect($address->pin_expires_at)->toBe(12000)->and($address->pin_hash)->toBe($hash);
});
