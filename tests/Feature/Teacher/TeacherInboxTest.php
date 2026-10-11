<?php

use App\Models\Address;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;

function ownAddress(?User $t = null): array
{
    $t ??= User::factory()->create();
    $g = Group::factory()->create(['user_id' => $t->id]);
    $a = Address::factory()->inGroup($g)->create(['local_part' => 'klasse7a-03']);

    return [$t, $a, $g];
}

it('keeps guests, pending teachers and other teachers out', function () {
    [$t, $a] = ownAddress();
    $this->get(route('addresses.inbox', $a))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->pending()->create())->get(route('addresses.inbox', $a))->assertRedirect(route('account.pending'));
    $this->actingAs(User::factory()->create())->get(route('addresses.inbox', $a))->assertNotFound();
    $this->getJson(route('addresses.messages', $a))->assertNotFound();
    $this->getJson(route('addresses.secret.show', $a))->assertNotFound();
});

it('shows the inbox page of an own address even without a valid PIN', function () {
    [$t, $a, $g] = ownAddress();
    $html = $this->actingAs($t)->get(route('addresses.inbox', $a))->assertOk()->assertSee('klasse7a-03@tmp-mail.example.org')->getContent();

    expect($html)->toContain('sandbox="allow-popups allow-popups-to-escape-sandbox"')->not->toContain('allow-scripts')->not->toContain('allow-same-origin')
        ->toContain(route('groups.show', $g));
});

it('lists, shows and deletes mails of an own address, also after the lesson ended', function () {
    [$t, $a] = ownAddress();
    $m = Message::factory()->create(['address_id' => $a->id, 'subject' => 'Hallo', 'body_text' => 'Text https://x.example/a', 'body_html' => '<p>Hi</p><script>x()</script>']);
    Message::factory()->create(['address_id' => Address::factory()->create()->id, 'subject' => 'Fremd']);

    $list = $this->actingAs($t)->getJson(route('addresses.messages', $a))->assertOk();
    expect($list->json('messages.*.subject'))->toBe(['Hallo'])->and($list->json('now'))->toBeInt();

    $show = $this->getJson(route('addresses.messages.show', [$a, $m]))->assertOk();
    expect($show->json('text_segments'))->toContain(['type' => 'link', 'value' => 'https://x.example/a'])
        ->and($show->json('html_document'))->toContain("default-src 'none'")->not->toContain('<script');

    $this->deleteJson(route('addresses.messages.destroy', [$a, $m]))->assertSuccessful();
    expect(Message::find($m->id))->toBeNull();
});

it('does not hand out mails of another address through an own address url', function () {
    [$t, $a] = ownAddress();
    $foreign = Message::factory()->create(['address_id' => Address::factory()->create(['user_id' => $t->id])->id]);
    $this->actingAs($t)->getJson(route('addresses.messages.show', [$a, $foreign]))->assertNotFound();
    $this->deleteJson(route('addresses.messages.destroy', [$a, $foreign]))->assertNotFound();
    expect(Message::find($foreign->id))->not->toBeNull();
});

it('lets the teacher read and change the secret of an own address', function () {
    [$t, $a] = ownAddress();
    $this->actingAs($t)->get(route('addresses.secret.edit', $a))->assertOk();
    $this->put(route('addresses.secret.update', $a), ['secret' => 'mein-passwort-beispiel'])->assertRedirect(route('addresses.inbox', $a));
    expect($a->fresh()->secret)->toBe('mein-passwort-beispiel');
    $this->getJson(route('addresses.secret.show', $a))->assertOk()->assertJson(['secret' => 'mein-passwort-beispiel']);
    $this->get(route('addresses.inbox', $a))->assertDontSee('mein-passwort-beispiel');
    $this->put(route('addresses.secret.update', $a), ['secret' => str_repeat('a', 501)])->assertSessionHasErrors('secret');
});

it('links from the group page to the inbox of every address', function () {
    [$t, $a, $g] = ownAddress();
    $this->actingAs($t)->get(route('groups.show', $g))->assertSee(route('addresses.inbox', $a), false);
});

it('shows the mail count per address on the group page', function () {
    [$t, $a, $g] = ownAddress();
    Message::factory()->count(3)->create(['address_id' => $a->id]);
    $this->actingAs($t)->get(route('groups.show', $g))->assertSeeInOrder(['klasse7a-03', '3']);
});
