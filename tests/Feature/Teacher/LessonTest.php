<?php

use App\Models\Address;
use App\Models\Group;
use App\Models\SenderRule;
use App\Models\User;
use App\Support\Clock;
use Illuminate\Support\Carbon;

function lessonSetup(int $addresses = 3, bool $sender = true): array
{
    $t = User::factory()->create();
    if ($sender) {
        SenderRule::create(['user_id' => $t->id, 'address_id' => null, 'pattern' => '@example-dienst.example']);
    }
    $g = Group::factory()->create(['user_id' => $t->id, 'name' => '7a Informatik']);
    Address::factory()->count($addresses)->inGroup($g)->sequence(fn ($s) => ['local_part' => 'klasse7a-0'.($s->index + 1)])->create();

    return [$t, $g];
}

it('starts a lesson of 45 minutes and issues fresh PINs to every address of the group', function () {
    [$t, $g] = lessonSetup(3);
    $now = Clock::now();

    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45])->assertRedirect(route('groups.show', $g));

    $g->refresh();
    expect($g->lesson_started_at)->toBeBetween($now, $now + 5)->and($g->lesson_ends_at - $g->lesson_started_at)->toBe(2700);
    $pins = Address::where('group_id', $g->id)->get();
    expect($pins->every(fn ($a) => $a->pin_expires_at === $g->lesson_ends_at && strlen($a->pin_encrypted) === 8))->toBeTrue();
    expect($pins->pluck('pin_encrypted')->unique())->toHaveCount(3);
});

it('only touches the addresses of that group', function () {
    [$t, $g] = lessonSetup(2);
    $loose = Address::factory()->create(['user_id' => $t->id, 'group_id' => null]);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    expect($loose->fresh()->pin_hash)->toBeNull();
});

it('replaces the PINs when a running lesson is started again', function () {
    [$t, $g] = lessonSetup(1);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $first = Address::where('group_id', $g->id)->first()->pin_encrypted;
    $this->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 90]);
    expect(Address::where('group_id', $g->id)->first()->pin_encrypted)->not->toBe($first);
    expect($g->fresh()->lesson_ends_at - $g->fresh()->lesson_started_at)->toBe(5400);
});

it('starts a lesson until a clock time today', function () {
    [$t, $g] = lessonSetup(1);
    $this->travelTo(Carbon::parse('2026-10-12 09:00:00', 'Europe/Berlin'));

    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'until', 'until' => '10:45'])->assertSessionHasNoErrors();

    expect($g->fresh()->lesson_ends_at)->toBe(Carbon::parse('2026-10-12 10:45:00', 'Europe/Berlin')->getTimestamp());
});

it('rejects an end time in the past and one beyond the maximum', function () {
    [$t, $g] = lessonSetup(1);
    $this->travelTo(Carbon::parse('2026-10-12 09:00:00', 'Europe/Berlin'));
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'until', 'until' => '08:30'])->assertSessionHasErrors('until');
    $this->post(route('groups.lesson.start', $g), ['mode' => 'until', 'until' => 'abc'])->assertSessionHasErrors('until');
    expect($g->fresh()->lesson_started_at)->toBeNull();
});

it('rejects durations that are too short or too long', function (int $minutes) {
    [$t, $g] = lessonSetup(1);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => $minutes])->assertSessionHasErrors('minutes');
    expect($g->fresh()->lesson_started_at)->toBeNull();
})->with([0, -5, 721, 100000]);

it('accepts the configured maximum', function () {
    [$t, $g] = lessonSetup(1);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 720])->assertSessionHasNoErrors();
});

it('cannot start without a sender entry or without addresses', function () {
    [$t, $g] = lessonSetup(1, false);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45])->assertSessionHasErrors('senders');
    expect(Address::first()->pin_hash)->toBeNull();

    [$t2, $empty] = lessonSetup(0);
    $this->actingAs($t2)->post(route('groups.lesson.start', $empty), ['mode' => 'minutes', 'minutes' => 45])->assertSessionHasErrors('addresses');
});

it('extends a running lesson by 15 minutes, moving every live PIN, up to the maximum', function () {
    [$t, $g] = lessonSetup(2);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $end = $g->fresh()->lesson_ends_at;
    $pin = Address::where('group_id', $g->id)->first()->pin_encrypted;

    $this->post(route('groups.lesson.extend', $g))->assertRedirect();

    expect($g->fresh()->lesson_ends_at)->toBe($end + 900);
    expect(Address::where('group_id', $g->id)->pluck('pin_expires_at')->unique()->all())->toBe([$end + 900])
        ->and(Address::where('group_id', $g->id)->first()->pin_encrypted)->toBe($pin);

    $this->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 720]);
    $this->post(route('groups.lesson.extend', $g));
    expect($g->fresh()->lesson_ends_at - $g->fresh()->lesson_started_at)->toBe(43200);
});

it('does not extend a lesson that is not running', function () {
    [$t, $g] = lessonSetup(1);
    $this->actingAs($t)->post(route('groups.lesson.extend', $g))->assertSessionHasErrors('lesson');
    $this->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $this->travel(46)->minutes();
    $this->post(route('groups.lesson.extend', $g))->assertSessionHasErrors('lesson');
});

it('ends a lesson at once: PINs invalid, plaintext gone, student sessions end', function () {
    [$t, $g] = lessonSetup(1);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $address = Address::where('group_id', $g->id)->first();
    $pin = $address->pin_encrypted;

    auth()->logout();
    $this->post(route('mailbox.login'), ['address' => $address->local_part, 'pin' => $pin])->assertRedirect(route('mailbox.inbox'));
    $this->get(route('mailbox.inbox'))->assertOk();

    $this->actingAs($t)->post(route('groups.lesson.end', $g))->assertRedirect();

    expect($address->fresh()->pinIsValid(Clock::now()))->toBeFalse()->and($address->fresh()->pin_encrypted)->toBeNull();
    expect($g->fresh()->window()->isActive(Clock::now()))->toBeFalse();
    auth()->logout();
    $this->get(route('mailbox.inbox'))->assertRedirect(route('home'));
});

it('regenerates all PINs during a running lesson, keeping the expiry', function () {
    [$t, $g] = lessonSetup(2);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $before = Address::where('group_id', $g->id)->pluck('pin_encrypted', 'id');

    $this->post(route('groups.pins.regenerate', $g))->assertRedirect();

    $after = Address::where('group_id', $g->id)->get();
    expect($after->every(fn ($a) => $a->pin_encrypted !== $before[$a->id] && $a->pin_expires_at === $g->fresh()->lesson_ends_at))->toBeTrue();
});

it('refuses to regenerate all PINs without a running lesson', function () {
    [$t, $g] = lessonSetup(1);
    $this->actingAs($t)->post(route('groups.pins.regenerate', $g))->assertSessionHasErrors('lesson');
});

it('regenerates a single PIN: lesson expiry if running, else the standalone validity', function () {
    [$t, $g] = lessonSetup(2);
    $a = Address::where('group_id', $g->id)->first();
    $b = Address::where('group_id', $g->id)->latest('id')->first();
    $now = Clock::now();

    $this->actingAs($t)->post(route('addresses.pin.regenerate', $a))->assertRedirect();
    expect($a->fresh()->pin_expires_at)->toBeBetween($now + 2700, $now + 2705);

    $this->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 90]);
    $old = $b->fresh()->pin_encrypted;
    $this->post(route('addresses.pin.regenerate', $b))->assertRedirect();
    expect($b->fresh()->pin_encrypted)->not->toBe($old)->and($b->fresh()->pin_expires_at)->toBe($g->fresh()->lesson_ends_at);

    $this->post(route('addresses.pin.regenerate', Address::factory()->create()))->assertNotFound();
});

it('a regenerated PIN cuts off the old one', function () {
    [$t, $g] = lessonSetup(1);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $a = Address::where('group_id', $g->id)->first();
    $old = $a->pin_encrypted;
    $this->post(route('addresses.pin.regenerate', $a));
    auth()->logout();
    $this->post(route('mailbox.login'), ['address' => $a->local_part, 'pin' => $old])->assertSessionHasErrors('address');
});

it('shows the beamer view with large address + PIN rows, countdown data and no teacher chrome', function () {
    [$t, $g] = lessonSetup(2);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $pin = Address::where('group_id', $g->id)->first()->pin_encrypted;

    $r = $this->get(route('groups.beamer', $g))->assertOk()
        ->assertSee('7a Informatik')->assertSee('klasse7a-01')->assertSee(substr($pin, 0, 4).' '.substr($pin, 4))
        ->assertSee(date('H:i', $g->fresh()->lesson_ends_at))->assertSee('tmp-mail.example.org');
    $html = $r->getContent();
    expect($html)->toContain('data-ends-at="'.$g->fresh()->lesson_ends_at.'"')->toContain('data-server-now="')->toContain('bg-beamer-bg');
});

it('the beamer view survives a reload and hides expired PINs', function () {
    [$t, $g] = lessonSetup(1);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $pin = Address::where('group_id', $g->id)->first()->pin_encrypted;
    $this->get(route('groups.beamer', $g))->assertSee(substr($pin, 0, 4));
    $this->get(route('groups.beamer', $g))->assertSee(substr($pin, 0, 4));

    $this->travel(46)->minutes();
    $this->get(route('groups.beamer', $g))->assertRedirect(route('groups.show', $g));
});

it('redirects the beamer without a lesson, and protects foreign groups', function () {
    [$t, $g] = lessonSetup(1);
    $this->actingAs($t)->get(route('groups.beamer', $g))->assertRedirect(route('groups.show', $g));
    $this->get(route('groups.beamer', Group::factory()->create()))->assertNotFound();
    $this->post(route('groups.lesson.start', Group::factory()->create()), ['mode' => 'minutes', 'minutes' => 45])->assertNotFound();
    $this->post(route('groups.lesson.end', Group::factory()->create()))->assertNotFound();
});

it('shows the lesson controls on the group page', function () {
    [$t, $g] = lessonSetup(1);
    $this->actingAs($t)->get(route('groups.show', $g))->assertOk()->assertSee(__('lesson.start'));
    $this->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $this->get(route('groups.show', $g))->assertSee(__('lesson.open_beamer'))->assertSee(__('lesson.end'))->assertSee(date('H:i', $g->fresh()->lesson_ends_at));
});
