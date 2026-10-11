<?php

use App\Core\Mail\AuthMode;
use App\Core\Mail\AuthResult;
use App\Core\Mail\SenderDecision;
use App\Core\Mail\SenderPolicy;
use App\Core\Mail\SenderWhitelist;

function pass(?string $domain): AuthResult
{
    return new AuthResult('pass', $domain);
}

function fail(?string $domain = null): AuthResult
{
    return new AuthResult('fail', $domain);
}

$wl = fn () => new SenderWhitelist(['@dienst.example']);

it('accepts a whitelisted sender with DMARC pass', function () use ($wl) {
    expect(SenderPolicy::decide($wl(), 'noreply@dienst.example', null, null, pass('dienst.example')))
        ->toBe(SenderDecision::Accept);
});

it('accepts with aligned DKIM', function () use ($wl) {
    expect(SenderPolicy::decide($wl(), 'noreply@dienst.example', fail(), pass('mail.dienst.example'), fail()))
        ->toBe(SenderDecision::Accept);
});

it('accepts with aligned SPF', function () use ($wl) {
    expect(SenderPolicy::decide($wl(), 'noreply@dienst.example', pass('dienst.example'), null, null))
        ->toBe(SenderDecision::Accept);
});

it('rejects pass results for an unrelated domain', function () use ($wl) {
    expect(SenderPolicy::decide($wl(), 'noreply@dienst.example', pass('evil.example'), pass('evil.example'), null))
        ->toBe(SenderDecision::Unauthenticated);
    expect(SenderPolicy::decide($wl(), 'noreply@dienst.example', pass('evil-dienst.example'), null, null))
        ->toBe(SenderDecision::Unauthenticated);
});

it('rejects when authentication data is missing or failed', function () use ($wl) {
    expect(SenderPolicy::decide($wl(), 'noreply@dienst.example', null, null, null))->toBe(SenderDecision::Unauthenticated);
    expect(SenderPolicy::decide($wl(), 'noreply@dienst.example', fail('dienst.example'), fail('dienst.example'), fail('dienst.example')))
        ->toBe(SenderDecision::Unauthenticated);
});

it('checks the whitelist first', function () use ($wl) {
    expect(SenderPolicy::decide($wl(), 'x@other.example', null, null, null))->toBe(SenderDecision::NotAllowed);
    expect(SenderPolicy::decide($wl(), 'x@other.example', pass('other.example'), pass('other.example'), pass('other.example')))
        ->toBe(SenderDecision::NotAllowed);
    expect(SenderPolicy::decide(new SenderWhitelist([]), 'a@b.example', pass('b.example'), null, pass('b.example')))
        ->toBe(SenderDecision::NotAllowed);
});

it('treats the pass result case-insensitively', function () {
    expect((new AuthResult('PASS', 'a.example'))->passed())->toBeTrue();
    expect((new AuthResult('softfail', 'a.example'))->passed())->toBeFalse();
});

it('accepts any passing check for any domain in authenticated mode', function () use ($wl) {
    $d = fn (?AuthResult $spf, ?AuthResult $dkim, ?AuthResult $dmarc) => SenderPolicy::decide($wl(), 'noreply@dienst.example', $spf, $dkim, $dmarc, AuthMode::Authenticated);

    expect($d(pass('mailserver.schule.example'), null, null))->toBe(SenderDecision::Accept);
    expect($d(null, pass('esp.example'), fail()))->toBe(SenderDecision::Accept);
    expect($d(null, null, pass('dienst.example')))->toBe(SenderDecision::Accept);
});

it('still needs one passing check in authenticated mode', function () use ($wl) {
    $d = fn (?AuthResult $spf, ?AuthResult $dkim, ?AuthResult $dmarc) => SenderPolicy::decide($wl(), 'noreply@dienst.example', $spf, $dkim, $dmarc, AuthMode::Authenticated);

    expect($d(null, null, null))->toBe(SenderDecision::Unauthenticated);
    expect($d(fail('x.example'), new AuthResult('none', null), fail()))->toBe(SenderDecision::Unauthenticated);
});

it('only checks the whitelist when authentication is off', function () use ($wl) {
    expect(SenderPolicy::decide($wl(), 'noreply@dienst.example', null, null, null, AuthMode::Off))->toBe(SenderDecision::Accept);
    expect(SenderPolicy::decide($wl(), 'x@other.example', pass('other.example'), null, pass('other.example'), AuthMode::Off))->toBe(SenderDecision::NotAllowed);
    expect(SenderPolicy::decide(new SenderWhitelist([]), 'a@b.example', null, null, null, AuthMode::Off))->toBe(SenderDecision::NotAllowed);
});

it('is strict by default and for unknown configuration values', function () use ($wl) {
    expect(SenderPolicy::decide($wl(), 'noreply@dienst.example', pass('esp.example'), null, null))->toBe(SenderDecision::Unauthenticated);
    expect(AuthMode::fromConfig('AUTHENTICATED '))->toBe(AuthMode::Authenticated)
        ->and(AuthMode::fromConfig('off'))->toBe(AuthMode::Off)
        ->and(AuthMode::fromConfig('nonsense'))->toBe(AuthMode::Aligned)
        ->and(AuthMode::fromConfig(null))->toBe(AuthMode::Aligned);
});
