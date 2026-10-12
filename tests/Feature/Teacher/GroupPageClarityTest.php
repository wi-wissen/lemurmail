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

it('shows the default sender list read-only on the group page and links to where it is edited', function () {
    [$t, $g] = clarityGroup();
    $html = $this->actingAs($t)->get(route('groups.show', $g))->getContent();

    expect($html)->toContain('@example-dienst.example')
        ->toContain(route('senders.index'))
        ->toContain(e(__('teacher.senders.edit_default')))
        ->not->toContain('action="'.route('senders.store').'"')
        ->not->toContain('action="'.route('senders.destroy', SenderRule::first()).'"');
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
        expect($teacher['senders'])->toHaveKeys(['edit_default', 'extra_for_address', 'scope_all', 'scope_extra']);
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
