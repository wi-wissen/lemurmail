<?php

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
