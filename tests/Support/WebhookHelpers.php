<?php

use App\Core\Webhook\SignatureVerifier;
use App\Models\Address;
use App\Models\SenderRule;
use App\Support\Clock;
use Illuminate\Support\Str;

/** Builds a `message.inbound` payload as Lettermint sends it. */
function inboundPayload(string $recipient, array $override = []): array
{
    return array_replace_recursive([
        'id' => (string) Str::uuid(),
        'event' => 'message.inbound',
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z', Clock::now()),
        'data' => [
            'message_id' => (string) Str::uuid(),
            'route' => 'catch-all',
            'recipient' => $recipient,
            'envelope' => ['mail_from' => 'bounce@mail.example-dienst.example', 'remote_ip' => '203.0.113.5'],
            'from' => ['email' => 'noreply@example-dienst.example', 'name' => 'Beispiel-Dienst'],
            'to' => [['email' => $recipient]],
            'subject' => 'Bitte bestätige deine E-Mail-Adresse',
            'date' => gmdate('Y-m-d\TH:i:s\Z', Clock::now()),
            'body' => ['text' => "Hallo\nhttps://example-dienst.example/confirm?t=1", 'html' => '<p>Hallo</p>'],
            'attachments' => [['filename' => 'a.pdf', 'content_type' => 'application/pdf', 'size' => 10]],
            'authentication_results' => [
                'spf' => ['result' => 'pass', 'domain' => 'mail.example-dienst.example'],
                'dkim' => ['result' => 'pass', 'domain' => 'example-dienst.example', 'selector' => 's1'],
                'dmarc' => ['result' => 'pass', 'domain' => 'example-dienst.example', 'policy' => 'none'],
            ],
            'is_spam' => false,
        ],
    ], $override);
}

/** POSTs a correctly signed webhook (or one with a custom signature header). */
function postWebhook($test, array $payload, ?string $signatureHeader = null, ?int $signedAt = null)
{
    $raw = json_encode($payload);
    $header = $signatureHeader ?? SignatureVerifier::sign($raw, config('lemurmail.webhook_secret'), $signedAt ?? Clock::now());

    return $test->call('POST', '/webhooks/lettermint', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_LETTERMINT_SIGNATURE' => $header,
    ], $raw);
}

/** Creates an address of a fresh teacher that has the default sender rule `@example-dienst.example`. */
function addressWithWhitelist(string $localPart = 'klasse7a-03', string $rule = '@example-dienst.example'): Address
{
    $address = Address::factory()->create(['local_part' => $localPart]);
    SenderRule::create(['user_id' => $address->user_id, 'address_id' => null, 'pattern' => $rule]);

    return $address;
}
