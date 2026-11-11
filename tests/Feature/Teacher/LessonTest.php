<?php

use App\Models\Address;
use App\Models\Group;
use App\Models\SenderRule;
use App\Models\User;
use App\Support\Clock;
use App\Support\TimeLabel;
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
    $this->get(route('mailbox.inbox'))->assertRedirect(route('mailbox.login.form'));
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

it('gives a single address its own PIN for the PIN maximum, with or without a running lesson', function () {
    [$t, $g] = lessonSetup(2);
    $a = Address::where('group_id', $g->id)->first();
    $b = Address::where('group_id', $g->id)->latest('id')->first();
    $now = Clock::now();
    $max = config('lemurmail.max_pin_seconds');

    $this->actingAs($t)->post(route('addresses.pin.regenerate', $a))->assertRedirect()
        ->assertSessionHas('status', __('lesson.pin_regenerated', ['until' => TimeLabel::until($a->fresh()->pin_expires_at, Clock::now())]));
    expect($a->fresh()->pin_expires_at)->toBeBetween($now + $max, $now + $max + 5);

    $this->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 90]);
    $old = $b->fresh()->pin_encrypted;
    $this->post(route('addresses.pin.regenerate', $b))->assertRedirect();
    expect($b->fresh()->pin_encrypted)->not->toBe($old)
        ->and($b->fresh()->pin_expires_at)->toBeBetween($now + $max, $now + $max + 5)
        ->and($b->fresh()->hasOwnPin($g->fresh()->lesson_ends_at, Clock::now()))->toBeTrue();

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

it('shows a name placeholder with the mail domain so pupils do not copy a sample address', function () {
    [$t, $g] = lessonSetup(2);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);

    $html = $this->get(route('groups.beamer', $g))->assertOk()->getContent();

    expect($html)->toContain(e('<name>'))->toContain('@<span')->toContain('tmp-mail.example.org')
        ->and($html)->toContain('x-show="lastMinute"');
});

it('takes back a single PIN, which then no longer works and is gone from the list', function () {
    [$t, $g] = lessonSetup(2);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    [$a, $b] = Address::where('group_id', $g->id)->get()->all();

    $this->delete(route('addresses.pin.revoke', $a))->assertRedirect();

    expect($a->fresh()->pinIsValid(Clock::now()))->toBeFalse()->and($a->fresh()->pin_encrypted)->toBeNull();
    expect($b->fresh()->pinIsValid(Clock::now()))->toBeTrue();
    // Only the address that still has a PIN offers the trash button.
    expect(substr_count($this->get(route('groups.show', $g))->getContent(), 'aria-label="'.__('teacher.addresses.revoke_pin').'"'))->toBe(1);
    $this->delete(route('addresses.pin.revoke', Address::factory()->create()))->assertNotFound();
});

/** Starts a 45 min lesson, then gives the first address of the group its own PIN. Returns [teacher, group, own, other]. */
function ownPinSetup($test): array
{
    [$t, $g] = lessonSetup(2);
    $test->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);
    $own = Address::where('group_id', $g->id)->first();
    $other = Address::where('group_id', $g->id)->latest('id')->first();
    $test->post(route('addresses.pin.regenerate', $own));

    return [$t, $g, $own->fresh(), $other->fresh()];
}

it('keeps an own PIN when the lesson is started again, all PINs are regenerated or the lesson is extended', function () {
    [, $g, $own, $other] = ownPinSetup($this);

    $this->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 90]);
    $this->post(route('groups.pins.regenerate', $g));
    $this->post(route('groups.lesson.extend', $g));

    expect($own->fresh()->pin_encrypted)->toBe($own->pin_encrypted)
        ->and($own->fresh()->pin_expires_at)->toBe($own->pin_expires_at)
        ->and($other->fresh()->pin_encrypted)->not->toBe($other->pin_encrypted)
        ->and($other->fresh()->pin_expires_at)->toBe($g->fresh()->lesson_ends_at);
});

it('keeps an own PIN valid when the lesson ends, the other PINs end', function () {
    [, $g, $own, $other] = ownPinSetup($this);

    $this->post(route('groups.lesson.end', $g));
    $now = Clock::now();

    expect($own->fresh()->pinIsValid($now))->toBeTrue()->and($own->fresh()->pin_encrypted)->toBe($own->pin_encrypted)
        ->and($other->fresh()->pinIsValid($now))->toBeFalse();

    auth()->logout();
    $this->post(route('mailbox.login'), ['address' => $own->local_part, 'pin' => $own->pin_encrypted])->assertRedirect(route('mailbox.inbox'));
});

it('hands the address back to the group when its own PIN is removed', function () {
    [, $g, $own] = ownPinSetup($this);

    $this->delete(route('addresses.pin.revoke', $own));
    $this->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);

    expect($own->fresh()->pin_encrypted)->not->toBe($own->pin_encrypted)
        ->and($own->fresh()->pin_expires_at)->toBe($g->fresh()->lesson_ends_at);
});

it('renews long lesson PINs when a new lesson starts', function () {
    [$t, $g] = lessonSetup(2);
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'date', 'until_date' => date('Y-m-d', Clock::now() + 30 * 86400)])->assertSessionHasNoErrors();
    $before = Address::where('group_id', $g->id)->pluck('pin_encrypted', 'id');

    $this->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 45]);

    Address::where('group_id', $g->id)->get()->each(function ($a) use ($before, $g) {
        expect($a->pin_encrypted)->not->toBe($before[$a->id])->and($a->pin_expires_at)->toBe($g->fresh()->lesson_ends_at);
    });
});

it('marks an own PIN only in the address list, the beamer shows it like any other', function () {
    [, $g, $own] = ownPinSetup($this);
    $marker = __('teacher.addresses.own_pin', ['until' => TimeLabel::until($own->pin_expires_at, Clock::now())]);

    $this->get(route('groups.show', $g))->assertOk()->assertSeeText($marker);
    $this->get(route('groups.beamer', $g))->assertOk()
        ->assertSee(substr($own->pin_encrypted, 0, 4).' '.substr($own->pin_encrypted, 4))
        ->assertDontSeeText($marker);
});
