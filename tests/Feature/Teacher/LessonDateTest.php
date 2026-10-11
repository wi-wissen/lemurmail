<?php

use App\Models\Address;
use App\Models\Group;
use App\Models\SenderRule;
use App\Models\User;
use App\Support\Clock;
use App\Support\TimeLabel;
use Illuminate\Support\Carbon;

function dateLesson(): array
{
    test()->travelTo(Carbon::parse('2026-10-12 09:00:00', 'Europe/Berlin'));
    $t = User::factory()->create();
    SenderRule::create(['user_id' => $t->id, 'address_id' => null, 'pattern' => '@example-dienst.example']);
    $g = Group::factory()->create(['user_id' => $t->id, 'name' => '7a Informatik']);
    Address::factory()->count(2)->inGroup($g)->sequence(fn ($s) => ['local_part' => 'klasse7a-0'.($s->index + 1)])->create();

    return [$t, $g];
}

function endOf(string $date): int
{
    return Carbon::parse($date.' 23:59:00', 'Europe/Berlin')->getTimestamp();
}

it('starts a lesson that ends on a date, far beyond the 12 hour limit', function () {
    [$t, $g] = dateLesson();

    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'date', 'until_date' => '2026-10-19'])
        ->assertSessionHasNoErrors()->assertRedirect(route('groups.show', $g));

    expect($g->fresh()->lesson_ends_at)->toBe(endOf('2026-10-19'));
    expect(Address::where('group_id', $g->id)->pluck('pin_expires_at')->unique()->all())->toBe([endOf('2026-10-19')]);
    expect(Address::where('group_id', $g->id)->whereNotNull('pin_encrypted')->count())->toBe(2);
});

it('accepts today as the end date', function () {
    [$t, $g] = dateLesson();
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'date', 'until_date' => '2026-10-12'])->assertSessionHasNoErrors();
    expect($g->fresh()->lesson_ends_at)->toBe(endOf('2026-10-12'));
});

it('rejects past dates, bad formats and dates beyond the maximum', function (string $date) {
    [$t, $g] = dateLesson();
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'date', 'until_date' => $date])->assertSessionHasErrors('until_date');
    expect($g->fresh()->lesson_started_at)->toBeNull();
})->with(['2026-10-11', 'morgen', '', '19.10.2026', '2027-12-31', '2027-01-11']);

it('accepts the last day inside the maximum (90 days in tests)', function () {
    [$t, $g] = dateLesson();
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'date', 'until_date' => '2027-01-09'])->assertSessionHasNoErrors();
});

it('keeps the 12 hour cap for duration based lessons', function () {
    [$t, $g] = dateLesson();
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'minutes', 'minutes' => 721])->assertSessionHasErrors('minutes');
});

it('extends a date based lesson by 15 minutes and clamps at the date maximum', function () {
    [$t, $g] = dateLesson();
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'date', 'until_date' => '2026-10-19']);
    $end = $g->fresh()->lesson_ends_at;

    $this->post(route('groups.lesson.extend', $g))->assertSessionHasNoErrors();
    expect($g->fresh()->lesson_ends_at)->toBe($end + 900);
    expect(Address::where('group_id', $g->id)->pluck('pin_expires_at')->unique()->all())->toBe([$end + 900]);

    $this->post(route('groups.lesson.start', $g), ['mode' => 'date', 'until_date' => '2027-01-09']);
    foreach (range(1, 45) as $i) { // the last valid date ends ~9 h before the cap: 45 x 15 min overshoots it
        $this->post(route('groups.lesson.extend', $g));
    }
    expect($g->fresh()->lesson_ends_at - $g->fresh()->lesson_started_at)->toBe(config('lemurmail.max_pin_seconds'));
});

it('lets students stay signed in for days and ends the access on the end date', function () {
    [$t, $g] = dateLesson();
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'date', 'until_date' => '2026-10-19']);
    $a = Address::where('group_id', $g->id)->first();
    $pin = $a->pin_encrypted;
    auth()->logout();

    $this->post(route('mailbox.login'), ['address' => $a->local_part, 'pin' => $pin])->assertRedirect(route('mailbox.inbox'));
    $this->travelTo(Carbon::parse('2026-10-18 12:00:00', 'Europe/Berlin'));
    $this->get(route('mailbox.inbox'))->assertOk();

    $this->travelTo(Carbon::parse('2026-10-20 00:00:01', 'Europe/Berlin'));
    $this->get(route('mailbox.inbox'))->assertRedirect(route('home'));
});

it('shows the end date on the group page, the beamer and the student inbox', function () {
    [$t, $g] = dateLesson();
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => 'date', 'until_date' => '2026-10-19']);
    $label = TimeLabel::until(endOf('2026-10-19'), Clock::now(), 'de');
    expect($label)->toBe('19.10. 23:59');
    $de = ['Accept-Language' => 'de'];

    $this->get(route('groups.show', $g), $de)->assertSee($label);
    $this->get(route('groups.beamer', $g), $de)->assertSee($label)->assertSee('data-ends-at="'.endOf('2026-10-19').'"', false);

    $a = Address::where('group_id', $g->id)->first();
    auth()->logout();
    $this->post(route('mailbox.login'), ['address' => $a->local_part, 'pin' => $a->pin_encrypted]);
    $this->get(route('mailbox.inbox'), $de)->assertSee($label);
});

it('offers the date option in the start form', function () {
    [$t, $g] = dateLesson();
    $html = $this->actingAs($t)->get(route('groups.show', $g))->getContent();
    expect($html)->toContain('name="until_date"')->toContain('type="date"');
});

it('accepts the exact payload the browser form sends for each mode (unused fields empty)', function (array $payload, string $field) {
    [$t, $g] = dateLesson();
    $this->actingAs($t)->post(route('groups.lesson.start', $g), $payload)->assertSessionHasNoErrors();
    expect($g->fresh()->lesson_started_at)->not->toBeNull();
})->with([
    'date' => [['mode' => 'date', 'minutes' => '', 'until_date' => '2026-10-19'], 'until_date'],
    'until time' => [['mode' => 'until', 'minutes' => '', 'until' => '18:30'], 'until'],
    'minutes' => [['mode' => 'minutes', 'minutes' => '45', 'until' => '', 'until_date' => ''], 'minutes'],
]);

it('still demands the field of the chosen mode when the browser sends it empty', function (string $mode, string $field) {
    [$t, $g] = dateLesson();
    $this->actingAs($t)->post(route('groups.lesson.start', $g), ['mode' => $mode, 'minutes' => '', 'until' => '', 'until_date' => ''])->assertSessionHasErrors($field);
})->with([['minutes', 'minutes'], ['until', 'until'], ['date', 'until_date']]);
