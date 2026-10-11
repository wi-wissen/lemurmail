<?php

use App\Core\Webhook\SignatureVerifier;

const SIG_NOW = 1_791_706_320;

it('verifies a correct signature', function () {
    $body = '{"id":"wh_1"}';
    $h = SignatureVerifier::sign($body, 'secret', SIG_NOW);
    expect($h)->toBe('t='.SIG_NOW.',v1='.hash_hmac('sha256', SIG_NOW.'.'.$body, 'secret'));
    expect(SignatureVerifier::verify($h, $body, 'secret', SIG_NOW))->toBeTrue();
});

it('rejects tampering', function () {
    $h = SignatureVerifier::sign('{"id":"wh_1"}', 'secret', SIG_NOW);
    expect(SignatureVerifier::verify($h, '{"id":"wh_2"}', 'secret', SIG_NOW))->toBeFalse();
    expect(SignatureVerifier::verify($h, '{"id":"wh_1"}', 'other', SIG_NOW))->toBeFalse();
    expect(SignatureVerifier::verify($h, '{"id":"wh_1"} ', 'secret', SIG_NOW))->toBeFalse();
});

it('enforces the time tolerance', function () {
    $h = SignatureVerifier::sign('b', 's', SIG_NOW);
    expect(SignatureVerifier::verify($h, 'b', 's', SIG_NOW + 300))->toBeTrue();
    expect(SignatureVerifier::verify($h, 'b', 's', SIG_NOW - 300))->toBeTrue();
    expect(SignatureVerifier::verify($h, 'b', 's', SIG_NOW + 301))->toBeFalse();
    expect(SignatureVerifier::verify($h, 'b', 's', SIG_NOW - 301))->toBeFalse();
    expect(SignatureVerifier::verify($h, 'b', 's', SIG_NOW + 600, 600))->toBeTrue();
});

it('rejects malformed input', function (string $header, string $secret) {
    expect(SignatureVerifier::verify($header, 'b', $secret, SIG_NOW))->toBeFalse();
})->with([
    ['', 's'],
    ['garbage', 's'],
    ['t=abc,v1=00', 's'],
    ['v1=00', 's'],
    ['t='.SIG_NOW, 's'],
    ['t='.SIG_NOW.',v1=', 's'],
]);

it('rejects an empty secret even with a matching hmac', function () {
    $h = 't='.SIG_NOW.',v1='.hash_hmac('sha256', SIG_NOW.'.b', '');
    expect(SignatureVerifier::verify($h, 'b', '', SIG_NOW))->toBeFalse();
});

it('accepts when any v1 value matches', function () {
    $good = hash_hmac('sha256', SIG_NOW.'.b', 's');
    expect(SignatureVerifier::verify('t='.SIG_NOW.',v1=deadbeef,v1='.$good, 'b', 's', SIG_NOW))->toBeTrue();
});
