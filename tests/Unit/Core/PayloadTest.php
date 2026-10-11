<?php

use App\Core\Webhook\InboundPayload;
use App\Core\Webhook\InvalidPayload;

function payload(array $override = []): array
{
    $base = [
        'id' => 'wh_123',
        'event' => 'message.inbound',
        'timestamp' => '2026-10-11T10:12:05Z',
        'data' => [
            'message_id' => 'msg-1',
            'recipient' => 'Klasse7A-03@tmp-mail.example.org',
            'envelope' => ['mail_from' => 'bounce@mail.dienst.example'],
            'from' => ['email' => 'NoReply@Dienst.example', 'name' => 'Dienst'],
            'to' => [['email' => 'klasse7a-03@tmp-mail.example.org']],
            'subject' => 'Bitte bestätige deine E-Mail-Adresse',
            'date' => '2026-10-11T10:12:00+02:00',
            'body' => ['text' => 'Hallo', 'html' => null],
            'attachments' => [['filename' => 'a.pdf', 'content_type' => 'application/pdf']],
            'authentication_results' => [
                'spf' => ['result' => 'pass', 'domain' => 'mail.dienst.example'],
                'dkim' => ['result' => 'pass', 'domain' => 'dienst.example', 'selector' => 's1'],
                'dmarc' => ['result' => 'pass', 'domain' => 'dienst.example', 'policy' => 'none'],
            ],
        ],
    ];

    return array_replace_recursive($base, $override);
}

it('parses a full payload', function () {
    $p = InboundPayload::fromArray(payload());
    expect($p->webhookId)->toBe('wh_123')
        ->and($p->recipient)->toBe('klasse7a-03@tmp-mail.example.org')
        ->and($p->recipientLocalPart())->toBe('klasse7a-03')
        ->and($p->recipientDomain())->toBe('tmp-mail.example.org')
        ->and($p->mailFrom)->toBe('bounce@mail.dienst.example')
        ->and($p->fromEmail)->toBe('noreply@dienst.example')
        ->and($p->subject)->toBe('Bitte bestätige deine E-Mail-Adresse')
        ->and($p->text)->toBe('Hallo')
        ->and($p->html)->toBeNull()
        ->and($p->receivedAt)->toBe(1_791_706_320)
        ->and($p->spf->passed())->toBeTrue()
        ->and($p->dkim->domain)->toBe('dienst.example')
        ->and($p->dmarc->result)->toBe('pass');
});

it('tolerates missing optional fields', function () {
    $json = payload();
    unset($json['data']['authentication_results'], $json['data']['subject'], $json['data']['envelope'], $json['data']['body']);
    $p = InboundPayload::fromArray($json);
    expect($p->spf)->toBeNull()->and($p->dkim)->toBeNull()->and($p->dmarc)->toBeNull()
        ->and($p->subject)->toBeNull()->and($p->mailFrom)->toBeNull()
        ->and($p->text)->toBeNull()->and($p->html)->toBeNull();
});

it('rejects invalid payloads', function (callable $mutate) {
    $json = payload();
    $mutate($json);
    InboundPayload::fromArray($json);
})->with([
    'no id' => [function (&$j) {
        unset($j['id']);
    }],
    'empty id' => [function (&$j) {
        $j['id'] = '';
    }],
    'wrong event' => [function (&$j) {
        $j['event'] = 'message.delivered';
    }],
    'no recipient' => [function (&$j) {
        unset($j['data']['recipient']);
    }],
    'null recipient' => [function (&$j) {
        $j['data']['recipient'] = null;
    }],
    'recipient without at' => [function (&$j) {
        $j['data']['recipient'] = 'nope';
    }],
    'no from' => [function (&$j) {
        unset($j['data']['from']);
    }],
    'bad date' => [function (&$j) {
        $j['data']['date'] = 'yesterday-ish';
    }],
    'no data' => [function (&$j) {
        unset($j['data']);
    }],
])->throws(InvalidPayload::class);
