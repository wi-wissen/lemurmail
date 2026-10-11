<?php

use App\Core\Webhook\SignatureVerifier;
use App\Models\Address;
use App\Models\Message;
use App\Models\SenderRule;
use Illuminate\Support\Facades\Log;

const MAIL_DOM = 'tmp-mail.example.org';

it('stores a signed mail for a known address', function () {
    $address = addressWithWhitelist('klasse7a-03');
    $payload = inboundPayload('klasse7a-03@'.MAIL_DOM);

    postWebhook($this, $payload)->assertOk();

    $m = Message::sole();
    expect($m->address_id)->toBe($address->id)
        ->and($m->webhook_id)->toBe($payload['id'])
        ->and($m->from_email)->toBe('noreply@example-dienst.example')
        ->and($m->subject)->toBe('Bitte bestätige deine E-Mail-Adresse')
        ->and($m->body_text)->toContain('https://example-dienst.example/confirm?t=1')
        ->and($m->body_html)->toBe('<p>Hallo</p>')
        ->and($m->received_at)->toBeInt();
});

it('routes case-insensitively and by envelope recipient, never by the To header', function () {
    $a = addressWithWhitelist('klasse7a-03');
    addressWithWhitelist('other');
    $payload = inboundPayload('Klasse7A-03@'.MAIL_DOM, ['data' => ['to' => [['email' => 'other@'.MAIL_DOM]]]]);

    postWebhook($this, $payload)->assertOk();

    expect(Message::sole()->address_id)->toBe($a->id);
});

it('rejects a missing or wrong signature', function () {
    addressWithWhitelist();
    $payload = inboundPayload('klasse7a-03@'.MAIL_DOM);

    postWebhook($this, $payload, 't=1,v1=bad')->assertStatus(401);
    postWebhook($this, $payload, '')->assertStatus(401);
    $this->postJson('/webhooks/lettermint', $payload)->assertStatus(401);

    expect(Message::count())->toBe(0);
});

it('rejects a signature made with another secret', function () {
    addressWithWhitelist();
    $payload = inboundPayload('klasse7a-03@'.MAIL_DOM);
    $header = SignatureVerifier::sign(json_encode($payload), 'not-the-secret', time());

    postWebhook($this, $payload, $header)->assertStatus(401);
    expect(Message::count())->toBe(0);
});

it('rejects a stale signature', function () {
    addressWithWhitelist();
    $payload = inboundPayload('klasse7a-03@'.MAIL_DOM);
    $raw = json_encode($payload);

    postWebhook($this, $payload, SignatureVerifier::sign($raw, 'test-secret', time() - 301))->assertStatus(401);
    expect(Message::count())->toBe(0);
});

it('fails closed when no webhook secret is configured', function () {
    addressWithWhitelist();
    config(['lemurmail.webhook_secret' => null]);
    $payload = inboundPayload('klasse7a-03@'.MAIL_DOM);
    $header = SignatureVerifier::sign(json_encode($payload), '', time());

    $this->call('POST', '/webhooks/lettermint', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_X_LETTERMINT_SIGNATURE' => $header,
    ], json_encode($payload))->assertStatus(401);
    expect(Message::count())->toBe(0);
});

it('is idempotent on the webhook id', function () {
    addressWithWhitelist();
    $payload = inboundPayload('klasse7a-03@'.MAIL_DOM);

    postWebhook($this, $payload)->assertOk();
    postWebhook($this, $payload)->assertOk();

    expect(Message::count())->toBe(1);
});

it('answers 2xx but stores nothing for unknown addresses or foreign domains', function () {
    addressWithWhitelist('klasse7a-03');

    postWebhook($this, inboundPayload('nobody@'.MAIL_DOM))->assertOk();
    postWebhook($this, inboundPayload('klasse7a-03@other-domain.example'))->assertOk();

    expect(Message::count())->toBe(0);
});

it('does not store mails from senders that are not whitelisted', function () {
    addressWithWhitelist('klasse7a-03', '@erlaubt.example');
    postWebhook($this, inboundPayload('klasse7a-03@'.MAIL_DOM))->assertOk();
    expect(Message::count())->toBe(0);
});

it('never accepts mail for an address with an empty whitelist', function () {
    Address::factory()->create(['local_part' => 'leer']);
    postWebhook($this, inboundPayload('leer@'.MAIL_DOM))->assertOk();
    expect(Message::count())->toBe(0);
});

it('honours per-address whitelist entries as well as the teacher default', function () {
    $address = addressWithWhitelist('klasse7a-03', '@erlaubt.example');
    SenderRule::create(['user_id' => $address->user_id, 'address_id' => $address->id, 'pattern' => 'noreply@example-dienst.example']);

    postWebhook($this, inboundPayload('klasse7a-03@'.MAIL_DOM))->assertOk();

    expect(Message::count())->toBe(1);
});

it('does not apply the per-address entries of another address', function () {
    $a = addressWithWhitelist('klasse7a-03', '@erlaubt.example');
    $b = Address::factory()->create(['local_part' => 'klasse7a-04', 'user_id' => $a->user_id]);
    SenderRule::create(['user_id' => $a->user_id, 'address_id' => $b->id, 'pattern' => '@example-dienst.example']);

    postWebhook($this, inboundPayload('klasse7a-03@'.MAIL_DOM))->assertOk();

    expect(Message::count())->toBe(0);
});

it('does not apply another teachers whitelist', function () {
    addressWithWhitelist('klasse7a-03', '@erlaubt.example');
    addressWithWhitelist('fremd', '@example-dienst.example');

    postWebhook($this, inboundPayload('klasse7a-03@'.MAIL_DOM))->assertOk();

    expect(Message::count())->toBe(0);
});

it('rejects a spoofed From header without authentication', function () {
    addressWithWhitelist();
    $payload = inboundPayload('klasse7a-03@'.MAIL_DOM, ['data' => ['authentication_results' => [
        'spf' => ['result' => 'fail', 'domain' => 'evil.example'],
        'dkim' => ['result' => 'none', 'domain' => null],
        'dmarc' => ['result' => 'fail', 'domain' => 'example-dienst.example'],
    ]]]);

    postWebhook($this, $payload)->assertOk();

    expect(Message::count())->toBe(0);
});

it('rejects mail without authentication results', function () {
    addressWithWhitelist();
    $payload = inboundPayload('klasse7a-03@'.MAIL_DOM);
    unset($payload['data']['authentication_results']);

    postWebhook($this, $payload)->assertOk();

    expect(Message::count())->toBe(0);
});

it('stores mail with a null text or html body', function () {
    addressWithWhitelist();
    postWebhook($this, inboundPayload('klasse7a-03@'.MAIL_DOM, ['data' => ['body' => ['text' => null, 'html' => null]]]))->assertOk();

    $m = Message::sole();
    expect($m->body_text)->toBeNull()->and($m->body_html)->toBeNull();
});

it('keeps mail even if the PIN has expired', function () {
    $address = addressWithWhitelist();
    $address->update(['pin_hash' => null, 'pin_expires_at' => 1]);
    postWebhook($this, inboundPayload('klasse7a-03@'.MAIL_DOM))->assertOk();
    expect(Message::count())->toBe(1);
});

it('answers 400 for a signed but malformed payload', function () {
    $raw = json_encode(['id' => 'x', 'event' => 'message.inbound', 'data' => []]);
    $this->call('POST', '/webhooks/lettermint', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_LETTERMINT_SIGNATURE' => SignatureVerifier::sign($raw, 'test-secret', time()),
    ], $raw)->assertStatus(400);
});

it('ignores other event types with 2xx', function () {
    addressWithWhitelist();
    $payload = inboundPayload('klasse7a-03@'.MAIL_DOM, ['event' => 'message.delivered']);
    postWebhook($this, $payload)->assertSuccessful();
    expect(Message::count())->toBe(0);
});

it('does not store attachments or raw data', function () {
    addressWithWhitelist();
    postWebhook($this, inboundPayload('klasse7a-03@'.MAIL_DOM))->assertOk();
    expect(array_keys(Message::sole()->getAttributes()))->not->toContain('attachments', 'raw');
});

it('does not write mail content to the log', function () {
    addressWithWhitelist();
    Log::spy();
    postWebhook($this, inboundPayload('klasse7a-03@'.MAIL_DOM))->assertOk();
    Log::shouldNotHaveReceived('info', fn ($m, $c = []) => str_contains(json_encode([$m, $c]), 'confirm?t=1'));
    expect(true)->toBeTrue();
});
