<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

it('sends system mail through the Lettermint HTTPS API', function () {
    config(['mail.mailers.lettermint.token' => 'lm_test']);
    Http::fake(['api.lettermint.co/*' => Http::response(['message_id' => 'x'], 202)]);

    Mail::mailer('lettermint')->raw('Hello', fn ($m) => $m->to('teacher@school.test')->subject('Hi'));

    Http::assertSent(fn ($r) => $r->url() === 'https://api.lettermint.co/v1/send'
        && $r->hasHeader('x-lettermint-token', 'lm_test')
        && $r['to'] === ['teacher@school.test']
        && $r['subject'] === 'Hi'
        && $r['text'] === 'Hello');
});

it('throws when the Lettermint API rejects the message', function () {
    config(['mail.mailers.lettermint.token' => 'bad']);
    Http::fake(['api.lettermint.co/*' => Http::response([], 401)]);

    Mail::mailer('lettermint')->raw('Hello', fn ($m) => $m->to('a@b.test')->subject('Hi'));
})->throws(Exception::class);
