<?php

use App\Models\Address;
use App\Models\Message;
use App\Support\Clock;

function signedIn($test, string $local = 'klasse7a-03', string $pin = 'K7M4QX9R'): Address
{
    $address = Address::factory()->withPin($pin)->create(['local_part' => $local]);
    $test->post(route('mailbox.login'), ['address' => $local, 'pin' => $pin])->assertRedirect(route('mailbox.inbox'));

    return $address;
}

it('requires a session for every mailbox endpoint', function () {
    $m = Message::factory()->create();
    $this->get(route('mailbox.inbox'))->assertRedirect(route('home'));
    $this->getJson(route('mailbox.messages'))->assertStatus(401);
    $this->getJson(route('mailbox.messages.show', $m))->assertStatus(401);
    $this->deleteJson(route('mailbox.messages.destroy', $m))->assertStatus(401);
    $this->get(route('mailbox.secret.edit'))->assertRedirect(route('home'));
    $this->getJson(route('mailbox.secret.show'))->assertStatus(401);
});

it('lists only the mails of the own address, newest first, with server time and expiry', function () {
    $address = signedIn($this);
    $other = Address::factory()->create();
    $now = Clock::now();
    $old = Message::factory()->create(['address_id' => $address->id, 'received_at' => $now - 600, 'subject' => 'Alt']);
    $new = Message::factory()->create(['address_id' => $address->id, 'received_at' => $now - 60, 'subject' => 'Neu']);
    Message::factory()->create(['address_id' => $other->id, 'subject' => 'Fremd']);

    $r = $this->getJson(route('mailbox.messages'))->assertOk();

    expect($r->json('messages.*.id'))->toBe([$new->id, $old->id])
        ->and($r->json('messages.0'))->toHaveKeys(['id', 'from', 'subject', 'received_at'])
        ->and($r->json('messages.0.subject'))->toBe('Neu')
        ->and($r->json('now'))->toBeInt()->and($r->json('now'))->toBeGreaterThanOrEqual($now)
        ->and($r->json('expires_at'))->toBe($address->fresh()->pin_expires_at);
});

it('shows text details with linkified segments and no html by default', function () {
    $address = signedIn($this);
    $m = Message::factory()->create([
        'address_id' => $address->id, 'from_email' => 'noreply@example-dienst.example', 'subject' => 'Bestätigen',
        'body_text' => "Hallo,\nbitte klicken: https://example-dienst.example/confirm?t=1.", 'body_html' => null,
    ]);

    $r = $this->getJson(route('mailbox.messages.show', $m))->assertOk();

    expect($r->json('subject'))->toBe('Bestätigen')->and($r->json('from'))->toBe('noreply@example-dienst.example')
        ->and($r->json('html_document'))->toBeNull()
        ->and($r->json('text_segments'))->toContain(['type' => 'link', 'value' => 'https://example-dienst.example/confirm?t=1'])
        ->and(collect($r->json('text_segments'))->pluck('value')->implode(''))->toBe($m->body_text);
});

it('delivers sanitized html as a complete sandbox document with a strict CSP', function () {
    $address = signedIn($this);
    $m = Message::factory()->create(['address_id' => $address->id, 'body_html' => '<p onclick="x()">Hi <b>du</b></p><script>alert(1)</script><img src="https://track.example/p.gif"><a href="javascript:alert(1)">bad</a><a href="https://ok.example/c">gut</a>']);

    $doc = $this->getJson(route('mailbox.messages.show', $m))->assertOk()->json('html_document');

    expect($doc)->toContain("default-src 'none'")->toContain('img-src data:')->toContain('<b>du</b>')->toContain('https://ok.example/c')
        ->not->toContain('<script')->not->toContain('onclick')->not->toContain('track.example')->not->toContain('javascript:');
});

it('renders the inbox page with a locked-down sandbox frame', function () {
    signedIn($this);
    $html = $this->get(route('mailbox.inbox'))->assertOk()->getContent();

    expect($html)->toContain('sandbox="allow-popups allow-popups-to-escape-sandbox"')
        ->toContain('referrerpolicy="no-referrer"')
        ->not->toContain('allow-scripts')->not->toContain('allow-same-origin');
});

it('shows the PIN validity and a logout in the inbox header', function () {
    $address = signedIn($this);
    $this->get(route('mailbox.inbox'))->assertSee(date('H:i', $address->pin_expires_at))->assertSee(__('common.logout'));
});

it('hides mails of other addresses', function () {
    signedIn($this);
    $foreign = Message::factory()->create();
    $this->getJson(route('mailbox.messages.show', $foreign))->assertNotFound();
    $this->deleteJson(route('mailbox.messages.destroy', $foreign))->assertNotFound();
    expect(Message::find($foreign->id))->not->toBeNull();
});

it('deletes an own mail', function () {
    $address = signedIn($this);
    $m = Message::factory()->create(['address_id' => $address->id]);
    $this->deleteJson(route('mailbox.messages.destroy', $m))->assertSuccessful();
    expect(Message::find($m->id))->toBeNull();
});

it('stores the secret encrypted and reveals it only on request', function () {
    $address = signedIn($this);

    $this->put(route('mailbox.secret.update'), ['secret' => 'mein-passwort-beispiel'])->assertRedirect(route('mailbox.inbox'));

    expect(DB::table('addresses')->where('id', $address->id)->value('secret'))->not->toBe('mein-passwort-beispiel')->not->toContain('mein-passwort');
    expect($address->fresh()->secret)->toBe('mein-passwort-beispiel');
    $this->getJson(route('mailbox.secret.show'))->assertOk()->assertJson(['secret' => 'mein-passwort-beispiel']);
    $this->get(route('mailbox.inbox'))->assertDontSee('mein-passwort-beispiel');
    $this->get(route('mailbox.secret.edit'))->assertOk();
});

it('clears the secret with an empty value and limits its length', function () {
    $address = signedIn($this);
    $address->update(['secret' => 'x']);
    $this->put(route('mailbox.secret.update'), ['secret' => ''])->assertRedirect();
    expect($address->fresh()->secret)->toBeNull();
    $this->put(route('mailbox.secret.update'), ['secret' => str_repeat('a', 501)])->assertSessionHasErrors('secret');
});
