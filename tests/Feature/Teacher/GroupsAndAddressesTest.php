<?php

use App\Models\Address;
use App\Models\Group;
use App\Models\Message;
use App\Models\SenderRule;
use App\Models\Setting;
use App\Models\User;

function teacher(array $attrs = []): User
{
    return User::factory()->create($attrs);
}

function withSender(User $u, string $pattern = '@example-dienst.example'): void
{
    SenderRule::create(['user_id' => $u->id, 'address_id' => null, 'pattern' => $pattern]);
}

it('keeps guests, pending and blocked teachers out', function () {
    $this->get(route('groups.index'))->assertRedirect(route('login'));
    $this->actingAs(teacher(['status' => 'pending']))->get(route('groups.index'))->assertRedirect(route('account.pending'));
    $this->actingAs(teacher(['status' => 'blocked']))->get(route('groups.index'))->assertRedirect(route('account.pending'));
});

it('creates, lists, renames and deletes groups of the teacher only', function () {
    $t = teacher();
    $other = Group::factory()->create(['name' => 'Fremde Gruppe']);

    $this->actingAs($t)->post(route('groups.store'), ['name' => '7a Informatik'])->assertRedirect();
    $g = Group::where('user_id', $t->id)->sole();
    $this->get(route('groups.index'))->assertOk()->assertSee('7a Informatik')->assertDontSee('Fremde Gruppe');

    $this->put(route('groups.update', $g), ['name' => '7b'])->assertRedirect();
    expect($g->fresh()->name)->toBe('7b');

    $this->get(route('groups.show', $other))->assertNotFound();
    $this->put(route('groups.update', $other), ['name' => 'x'])->assertNotFound();
    $this->delete(route('groups.destroy', $other))->assertNotFound();

    $addr = Address::factory()->create(['user_id' => $t->id, 'group_id' => $g->id]);
    $this->delete(route('groups.destroy', $g))->assertRedirect(route('groups.index'));
    expect(Group::find($g->id))->toBeNull()->and($addr->fresh()->group_id)->toBeNull();
});

it('validates the group name', function () {
    $this->actingAs(teacher())->post(route('groups.store'), ['name' => ''])->assertSessionHasErrors('name');
});

it('shows the group page with addresses, PINs hidden when none are valid', function () {
    $t = teacher();
    withSender($t);
    $g = Group::factory()->create(['user_id' => $t->id, 'name' => '7a Informatik']);
    Address::factory()->inGroup($g)->create(['local_part' => 'klasse7a-01']);
    Address::factory()->inGroup($g)->withPin('T3WD8HNP')->create(['local_part' => 'klasse7a-02']);

    $this->actingAs($t)->get(route('groups.show', $g))->assertOk()
        ->assertSee('7a Informatik')->assertSee('klasse7a-01')->assertSee('klasse7a-02')->assertSee('T3WD 8HNP')
        ->assertSee('@example-dienst.example');
});

it('creates an address series in the group with zero padding', function () {
    $t = teacher();
    withSender($t);
    $g = Group::factory()->create(['user_id' => $t->id]);

    $this->actingAs($t)->post(route('groups.addresses.store', $g), ['prefix' => 'klasse7a-', 'count' => 30])->assertRedirect(route('groups.show', $g));

    $locals = Address::where('group_id', $g->id)->orderBy('id')->pluck('local_part');
    expect($locals)->toHaveCount(30)->and($locals->first())->toBe('klasse7a-01')->and($locals->last())->toBe('klasse7a-30');
    expect(Address::where('group_id', $g->id)->whereNotNull('pin_hash')->count())->toBe(0);
    expect(Address::where('group_id', $g->id)->where('user_id', $t->id)->count())->toBe(30);
});

it('refuses to create addresses without any sender entry', function () {
    $t = teacher();
    $g = Group::factory()->create(['user_id' => $t->id]);

    $this->actingAs($t)->post(route('groups.addresses.store', $g), ['prefix' => 'klasse7a-', 'count' => 3])->assertSessionHasErrors('prefix');
    expect(Address::count())->toBe(0);
});

it('creates nothing when one local part is taken (all or nothing)', function () {
    $t = teacher();
    withSender($t);
    $g = Group::factory()->create(['user_id' => $t->id]);
    Address::factory()->create(['local_part' => 'klasse7a-02']);

    $this->actingAs($t)->post(route('groups.addresses.store', $g), ['prefix' => 'klasse7a-', 'count' => 5])->assertSessionHasErrors('prefix');
    expect(Address::count())->toBe(1);
});

it('enforces the per-teacher address cap', function () {
    $t = teacher(['address_limit' => 4]);
    withSender($t);
    $g = Group::factory()->create(['user_id' => $t->id]);

    $this->actingAs($t)->post(route('groups.addresses.store', $g), ['prefix' => 'a-', 'count' => 5])->assertSessionHasErrors('count');
    expect(Address::count())->toBe(0);
    $this->post(route('groups.addresses.store', $g), ['prefix' => 'a-', 'count' => 4])->assertSessionHasNoErrors();
    $this->post(route('groups.addresses.store', $g), ['prefix' => 'b-', 'count' => 1])->assertSessionHasErrors('count');
});

it('uses the global cap setting when the teacher has no override', function () {
    Setting::put('address_limit', 2);
    $t = teacher();
    withSender($t);
    $g = Group::factory()->create(['user_id' => $t->id]);
    $this->actingAs($t)->post(route('groups.addresses.store', $g), ['prefix' => 'a-', 'count' => 3])->assertSessionHasErrors('count');
});

it('rejects invalid prefixes and counts', function (array $input) {
    $t = teacher();
    withSender($t);
    $g = Group::factory()->create(['user_id' => $t->id]);
    $this->actingAs($t)->post(route('groups.addresses.store', $g), $input)->assertSessionHasErrors();
    expect(Address::count())->toBe(0);
})->with([
    [['prefix' => 'Bad Prefix', 'count' => 3]],
    [['prefix' => 'ok-', 'count' => 0]],
    [['prefix' => 'ok-', 'count' => 'abc']],
    [['prefix' => '', 'count' => 3]],
    [['prefix' => 'ok-', 'count' => 100000]],
]);

it('creates a single address', function () {
    $t = teacher();
    withSender($t);
    $g = Group::factory()->create(['user_id' => $t->id]);
    $this->actingAs($t)->post(route('addresses.store'), ['local_part' => 'Max.Mustermann', 'group_id' => $g->id])->assertRedirect();
    $a = Address::sole();
    expect($a->local_part)->toBe('max.mustermann')->and($a->group_id)->toBe($g->id)->and($a->user_id)->toBe($t->id);
});

it('does not let a teacher put an address into a foreign group', function () {
    $t = teacher();
    withSender($t);
    $foreign = Group::factory()->create();
    $this->actingAs($t)->post(route('addresses.store'), ['local_part' => 'x', 'group_id' => $foreign->id])->assertSessionHasErrors('group_id');
});

it('deletes own addresses with their mails and refuses foreign ones', function () {
    $t = teacher();
    $a = Address::factory()->create(['user_id' => $t->id]);
    Message::factory()->create(['address_id' => $a->id]);
    $foreign = Address::factory()->create();

    $this->actingAs($t)->delete(route('addresses.destroy', $foreign))->assertNotFound();
    $this->delete(route('addresses.destroy', $a))->assertRedirect();

    expect(Address::find($a->id))->toBeNull()->and(Message::count())->toBe(0)->and(Address::find($foreign->id))->not->toBeNull();
});

it('manages the default sender list', function () {
    $t = teacher();
    $this->actingAs($t)->post(route('senders.store'), ['pattern' => ' @Example-Dienst.example '])->assertRedirect();
    $this->post(route('senders.store'), ['pattern' => 'hello@pixelwerk.example'])->assertRedirect();
    $this->post(route('senders.store'), ['pattern' => '@example-dienst.example'])->assertRedirect(); // duplicate ignored

    $rules = SenderRule::where('user_id', $t->id)->whereNull('address_id')->pluck('pattern');
    expect($rules->sort()->values()->all())->toBe(['@example-dienst.example', 'hello@pixelwerk.example']);
    $this->get(route('senders.index'))->assertOk()->assertSee('@example-dienst.example')->assertSee('hello@pixelwerk.example');

    $this->post(route('senders.store'), ['pattern' => 'not valid'])->assertSessionHasErrors('pattern');

    $this->delete(route('senders.destroy', SenderRule::first()))->assertRedirect();
    expect(SenderRule::count())->toBe(1);
});

it('manages per-address sender entries and protects foreign rules', function () {
    $t = teacher();
    $a = Address::factory()->create(['user_id' => $t->id]);
    $this->actingAs($t)->post(route('addresses.senders.store', $a), ['pattern' => '@extra.example'])->assertRedirect();
    expect(SenderRule::where('address_id', $a->id)->value('pattern'))->toBe('@extra.example');

    $foreignRule = SenderRule::create(['user_id' => User::factory()->create()->id, 'address_id' => null, 'pattern' => '@x.example']);
    $this->delete(route('senders.destroy', $foreignRule))->assertNotFound();
    $this->post(route('addresses.senders.store', Address::factory()->create()), ['pattern' => '@y.example'])->assertNotFound();
});

it('exports the live PINs of a group as CSV and a print view', function () {
    $t = teacher();
    $g = Group::factory()->create(['user_id' => $t->id]);
    Address::factory()->inGroup($g)->withPin('T3WD8HNP', 1_900_000_000)->create(['local_part' => 'klasse7a-02']);
    Address::factory()->inGroup($g)->create(['local_part' => 'klasse7a-01']);

    $csv = $this->actingAs($t)->get(route('groups.pins.csv', $g))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($csv->streamedContent())->toContain('address,pin,valid_until')->toContain('klasse7a-02@tmp-mail.example.org,T3WD8HNP')->not->toContain('klasse7a-01');
    $this->get(route('groups.print', $g))->assertOk()->assertSee('klasse7a-02')->assertSee('T3WD 8HNP');
    $this->get(route('groups.pins.csv', Group::factory()->create()))->assertNotFound();
});
