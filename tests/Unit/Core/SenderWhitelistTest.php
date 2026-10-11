<?php

use App\Core\Mail\SenderWhitelist;

it('matches exact addresses case-insensitively', function () {
    $w = new SenderWhitelist(['Hello@Pixelwerk.example']);
    expect($w->matches('hello@pixelwerk.example'))->toBeTrue();
    expect($w->matches('HELLO@PIXELWERK.EXAMPLE'))->toBeTrue();
    expect($w->matches('other@pixelwerk.example'))->toBeFalse();
});

it('matches whole domains with and without @', function (string $pattern) {
    $w = new SenderWhitelist([$pattern]);
    expect($w->matches('noreply@example-dienst.example'))->toBeTrue();
    expect($w->matches('a@sub.example-dienst.example'))->toBeFalse();
    expect($w->matches('a@evil-example-dienst.example'))->toBeFalse();
    expect($w->matches('a@example-dienst.example.evil.org'))->toBeFalse();
})->with(['@example-dienst.example', 'example-dienst.example', ' @Example-Dienst.example ']);

it('matches nothing when empty', function () {
    $w = new SenderWhitelist([]);
    expect($w->isEmpty())->toBeTrue()->and($w->matches('a@b.example'))->toBeFalse();
    expect((new SenderWhitelist(['', '  ']))->isEmpty())->toBeTrue();
});

it('never matches malformed addresses', function () {
    $w = new SenderWhitelist(['@example.org']);
    expect($w->matches('example.org'))->toBeFalse();
    expect($w->matches('a@b@example.org'))->toBeFalse();
    expect($w->matches(''))->toBeFalse();
});

it('normalizes patterns', function () {
    expect(SenderWhitelist::normalizePattern(' @X.org '))->toBe('@x.org');
    expect(SenderWhitelist::normalizePattern('X.org'))->toBe('@x.org');
    expect(SenderWhitelist::normalizePattern('A@X.org'))->toBe('a@x.org');
    expect(SenderWhitelist::normalizePattern('@'))->toBeNull();
    expect(SenderWhitelist::normalizePattern('not valid'))->toBeNull();
    expect(SenderWhitelist::normalizePattern(''))->toBeNull();
});
