<?php

use App\Models\Address;
use App\Models\Group;
use App\Models\User;

it('offers a copy button behind the address on the teacher inbox page', function () {
    $t = User::factory()->create();
    $g = Group::factory()->create(['user_id' => $t->id]);
    $a = Address::factory()->inGroup($g)->create(['local_part' => 'klasse7a-03']);

    $html = $this->actingAs($t)->get(route('addresses.inbox', $a))->assertOk()->getContent();

    expect($html)->toContain('x-data="copyButton"')
        ->toContain('data-copy="klasse7a-03@tmp-mail.example.org"')
        ->toContain('aria-label="'.e(__('mailbox.copy_address')).'"')
        ->toContain('data-copied-label="'.e(__('mailbox.copied')).'"');
    expect($html)->toContain('@click="copy()"');
    expect($html)->toMatch('~<h1[^>]*>.*klasse7a-03@tmp-mail\.example\.org.*</h1>.*x-data="copyButton"~s');
});

it('offers the same copy button next to the address in the student inbox header', function () {
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    $this->post(route('mailbox.login'), ['address' => 'klasse7a-03', 'pin' => 'K7M4QX9R']);

    $html = $this->get(route('mailbox.inbox'))->assertOk()->getContent();

    expect($html)->toContain('x-data="copyButton"')->toContain('data-copy="klasse7a-03@tmp-mail.example.org"');
});

it('has the strings for the button in both languages', function () {
    foreach (['de', 'en'] as $locale) {
        $strings = trans('mailbox', [], $locale);
        expect($strings)->toHaveKeys(['copy_address', 'copied']);
    }
});
