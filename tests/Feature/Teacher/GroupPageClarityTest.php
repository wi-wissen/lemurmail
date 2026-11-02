<?php

use App\Models\Address;
use App\Models\Group;
use App\Models\SenderRule;
use App\Models\User;

function clarityGroup(): array
{
    $t = User::factory()->create();
    SenderRule::create(['user_id' => $t->id, 'address_id' => null, 'pattern' => '@example-dienst.example']);
    $g = Group::factory()->create(['user_id' => $t->id, 'name' => '7a Informatik']);
    $addresses = Address::factory()->count(3)->inGroup($g)->sequence(fn ($s) => ['local_part' => 'klasse7a-0'.($s->index + 1)])->create();

    return [$t, $g, $addresses];
}

it('explains that deleting a group deletes its addresses and mails and asks for confirmation', function () {
    [$t, $g] = clarityGroup();
    $html = $this->actingAs($t)->get(route('groups.show', $g))->assertOk()->getContent();

    expect($html)->toContain(e(__('teacher.groups.delete_hint', ['count' => 3])))
        ->toContain('data-confirm="'.e(__('teacher.groups.delete_confirm', ['count' => 3])).'"');
    expect(__('teacher.groups.delete_hint', ['count' => 3], 'de'))->toContain('3')->not->toContain('bleiben erhalten');
});

it('does not repeat the global sender list on the group page', function () {
    [$t, $g] = clarityGroup();
    $html = $this->actingAs($t)->get(route('groups.show', $g))->getContent();

    expect($html)->not->toContain('action="'.route('senders.store').'"');
});

it('still lets a teacher add extra senders for one address, clearly labelled', function () {
    [$t, $g, $addresses] = clarityGroup();
    $html = $this->actingAs($t)->get(route('groups.show', $g))->getContent();

    expect($html)->toContain('action="'.route('addresses.senders.store', $addresses[0]).'"')->toContain(e(__('teacher.senders.extra_for_address')));
});

it('explains on the senders page what the list means', function () {
    [$t] = clarityGroup();
    $html = $this->actingAs($t)->get(route('senders.index'))->assertOk()->getContent();

    expect($html)->toContain(e(__('teacher.senders.scope_all')))->toContain(e(__('teacher.senders.scope_extra')))
        ->toContain('action="'.route('senders.store').'"');
});

it('has the new strings in both languages', function () {
    foreach (['de', 'en'] as $locale) {
        $teacher = trans('teacher', [], $locale);
        expect($teacher['groups'])->toHaveKeys(['delete_hint', 'delete_confirm']);
        expect($teacher['senders'])->toHaveKeys(['extra_for_address', 'scope_all', 'scope_extra']);
    }
});

it('opens the beamer in a new tab and keeps the label readable on hover', function () {
    [$t, $g] = clarityGroup();
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $html = $this->get(route('groups.show', $g))->getContent();

    expect($html)->toMatch('~<a [^>]*href="'.preg_quote(route('groups.beamer', $g), '~').'"[^>]*>~');
    preg_match('~<a [^>]*href="'.preg_quote(route('groups.beamer', $g), '~').'"[^>]*>~', $html, $m);
    expect($m[0])->toContain('target="_blank"')->toContain('rel="noopener"')->toContain('hover:text-white');
});

it('splits the group page into addresses and settings tabs (the sender list is not group-specific, so no tab)', function () {
    [$t, $g] = clarityGroup();
    $html = $this->actingAs($t)->get(route('groups.show', $g))->assertOk()->getContent();

    foreach (['addresses', 'settings'] as $tab) {
        expect($html)->toContain('id="panel-'.$tab.'"')->toContain('id="tab-'.$tab.'"');
    }
    expect($html)->not->toContain('id="tab-senders"')->toContain("groupTabs('addresses')");
});

it('opens the settings tab when renaming the group failed validation', function () {
    [$t, $g] = clarityGroup();
    $this->actingAs($t)->from(route('groups.show', $g))->put(route('groups.update', $g), ['name' => ''])->assertRedirect(route('groups.show', $g));

    $this->get(route('groups.show', $g))->assertSee("groupTabs('settings')", false);
});

it('sends a teacher without any allowed sender to the senders page after login, others to the groups', function () {
    $t = User::factory()->create();
    $this->actingAs($t)->get(route('dashboard'))->assertRedirect(route('senders.index'));

    SenderRule::create(['user_id' => $t->id, 'address_id' => null, 'pattern' => '@example-dienst.example']);
    $this->actingAs($t)->get(route('dashboard'))->assertRedirect(route('groups.index'));
});

it('warns on the group page when no mail can arrive because no sender is allowed', function () {
    $t = User::factory()->create();
    $g = Group::factory()->create(['user_id' => $t->id]);

    $this->actingAs($t)->get(route('groups.show', $g))->assertSee(__('teacher.groups.no_senders_title'));

    $a = Address::factory()->inGroup($g)->create(['local_part' => 'a-01']);
    Address::factory()->inGroup($g)->create(['local_part' => 'a-02']);
    $this->actingAs($t)->get(route('groups.show', $g))
        ->assertSee(trans_choice('teacher.groups.no_senders_silent', 2, ['count' => 2, 'total' => 2]));

    SenderRule::create(['user_id' => $t->id, 'address_id' => $a->id, 'pattern' => 'lms.example']);
    $this->actingAs($t)->get(route('groups.show', $g))
        ->assertSee(trans_choice('teacher.groups.no_senders_silent', 1, ['count' => 1, 'total' => 2]));
});

it('shows no sender warning when a default sender exists', function () {
    [$t, $g] = clarityGroup();

    $this->actingAs($t)->get(route('groups.show', $g))->assertDontSee(__('teacher.groups.no_senders_title'));
});

it('keeps the per-address senders collapsed behind an action and opens the row that had an error', function () {
    [$t, $g, $addresses] = clarityGroup();
    $first = $addresses[0];

    $html = $this->actingAs($t)->get(route('groups.show', $g))->getContent();
    expect($html)->toContain('aria-controls="senders-'.$first->id.'"')->not->toContain('<details');

    $this->from(route('groups.show', $g))->post(route('addresses.senders.store', $first), ['pattern' => 'kein gültiger Wert!', 'row' => $first->id])
        ->assertRedirect(route('groups.show', $g));
    expect($this->get(route('groups.show', $g))->getContent())->toContain('x-data="{ open: true }"');
});
