<?php

use App\Models\Address;
use App\Models\Group;
use App\Models\OrganizationDomain;
use App\Models\SenderRule;
use App\Models\User;

/** Every form needs a button that really submits (a type="button" default once broke most forms). */
function assertEveryFormCanSubmit(string $html): void
{
    $doc = new DOMDocument;
    @$doc->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $xpath = new DOMXPath($doc);
    $forms = $xpath->query('//form');
    expect($forms->length)->toBeGreaterThan(0);

    foreach ($forms as $form) {
        $id = $form->getAttribute('id');
        $outside = $id === '' ? '' : ' | //button[@form="'.$id.'" and (not(@type) or @type="submit")]';
        $submit = $xpath->query('.//button[not(@type) or @type="submit"] | .//input[@type="submit"]'.$outside, $form);
        expect($submit->length)->toBeGreaterThan(0, 'form without a submit button: '.$form->getAttribute('action'));
    }
}

it('gives every form on the teacher pages a submit button', function () {
    $t = User::factory()->create();
    SenderRule::create(['user_id' => $t->id, 'address_id' => null, 'pattern' => '@example-dienst.example']);
    $g = Group::factory()->create(['user_id' => $t->id]);
    Address::factory()->inGroup($g)->withPin()->create(['local_part' => 'klasse7a-01']);

    $this->actingAs($t);
    foreach ([route('groups.index'), route('groups.show', $g), route('senders.index'), route('profile.edit')] as $url) {
        assertEveryFormCanSubmit($this->get($url)->assertOk()->getContent());
    }

    $this->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    assertEveryFormCanSubmit($this->get(route('groups.show', $g))->getContent());
    assertEveryFormCanSubmit($this->get(route('groups.beamer', $g))->assertOk()->getContent());
});

it('gives every form on the admin pages a submit button', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    User::factory()->pending()->create();
    OrganizationDomain::create(['domain' => 'schule.example.org']);

    foreach ([route('admin.users.index'), route('admin.domains.index')] as $url) {
        assertEveryFormCanSubmit($this->get($url)->assertOk()->getContent());
    }
});

it('gives every form on the public and student pages a submit button', function () {
    assertEveryFormCanSubmit($this->get(route('home'))->getContent());
    assertEveryFormCanSubmit($this->get(route('login'))->getContent());
    assertEveryFormCanSubmit($this->get(route('register'))->getContent());
    assertEveryFormCanSubmit($this->get(route('password.request'))->getContent());

    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    $this->post(route('mailbox.login'), ['address' => 'klasse7a-03', 'pin' => 'K7M4QX9R']);
    assertEveryFormCanSubmit($this->get(route('mailbox.inbox'))->getContent());
    assertEveryFormCanSubmit($this->get(route('mailbox.secret.edit'))->getContent());
});
