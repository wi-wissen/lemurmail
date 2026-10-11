<?php

use App\Models\Address;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;

function seedUsers(): array
{
    $now = Carbon::parse('2026-10-12 12:00:00', 'Europe/Berlin');
    $mk = fn (string $email, string $status, int $daysAgo, int $addresses) => tap(
        User::factory()->create(['email' => $email, 'status' => $status, 'created_at' => $now->copy()->subDays($daysAgo)]),
        fn ($u) => Address::factory()->count($addresses)->create(['user_id' => $u->id]),
    );

    return [
        'old' => $mk('alt@schule.example.org', 'active', 100, 2),
        'busy' => $mk('viele@schule.example.org', 'active', 10, 9),
        'wait' => $mk('wartet@andere.example', 'pending', 1, 0),
        'blocked' => $mk('gesperrt@schule.example.org', 'blocked', 50, 1),
    ];
}

function emailsInOrder(string $html): array
{
    preg_match_all('/[a-z]+@[a-z.]+\.(?:org|example)/', substr($html, strpos($html, '<table')), $m);

    return array_values(array_unique(array_filter($m[0], fn ($e) => $e !== 'admin@verein.example' && ! str_starts_with($e, 'name@'))));
}

beforeEach(fn () => $this->actingAs(User::factory()->superAdmin()->create(['email' => 'admin@verein.example'])));

it('sorts by sign-up date in both directions', function () {
    seedUsers();
    $asc = emailsInOrder($this->get(route('admin.users.index', ['sort' => 'created', 'dir' => 'asc']))->getContent());
    $desc = emailsInOrder($this->get(route('admin.users.index', ['sort' => 'created', 'dir' => 'desc']))->getContent());

    expect($asc[0])->toBe('alt@schule.example.org')->and($desc[0])->not->toBe('alt@schule.example.org')
        ->and(array_search('wartet@andere.example', $asc))->toBeGreaterThan(array_search('viele@schule.example.org', $asc));
    expect(array_reverse($asc))->toBe($desc);
});

it('sorts by number of addresses', function () {
    seedUsers();
    $desc = emailsInOrder($this->get(route('admin.users.index', ['sort' => 'addresses', 'dir' => 'desc']))->getContent());
    $asc = emailsInOrder($this->get(route('admin.users.index', ['sort' => 'addresses', 'dir' => 'asc']))->getContent());

    expect($desc[0])->toBe('viele@schule.example.org')->and($asc[0])->toBe('wartet@andere.example');
});

it('sorts by email and by status (pending first, then active, rejected, blocked)', function () {
    seedUsers();
    $email = emailsInOrder($this->get(route('admin.users.index', ['sort' => 'email', 'dir' => 'asc']))->getContent());
    expect($email)->toBe(collect($email)->sort()->values()->all());

    $status = emailsInOrder($this->get(route('admin.users.index', ['sort' => 'status', 'dir' => 'asc']))->getContent());
    expect($status[0])->toBe('wartet@andere.example')->and(end($status))->toBe('gesperrt@schule.example.org');
});

it('keeps pending accounts first by default', function () {
    seedUsers();
    expect(emailsInOrder($this->get(route('admin.users.index'))->getContent())[0])->toBe('wartet@andere.example');
});

it('ignores unknown sort columns and directions', function () {
    seedUsers();
    $this->get(route('admin.users.index', ['sort' => 'password; drop table', 'dir' => 'sideways']))->assertOk();
});

it('marks the sorted column and offers header links that flip the direction', function () {
    seedUsers();
    $html = $this->get(route('admin.users.index', ['sort' => 'addresses', 'dir' => 'desc']))->getContent();

    expect($html)->toContain('aria-sort="descending"')->toContain(e(route('admin.users.index', ['sort' => 'addresses', 'dir' => 'asc'])))
        ->toContain(e(route('admin.users.index', ['sort' => 'created', 'dir' => 'asc'])));
    $asc = $this->get(route('admin.users.index', ['sort' => 'created', 'dir' => 'asc']))->getContent();
    expect($asc)->toContain('aria-sort="ascending"');
});

it('does not repeat the default limit text in every row and keeps the row compact', function () {
    seedUsers();
    $html = $this->get(route('admin.users.index'))->getContent();

    expect($html)->not->toContain('Standard (')->not->toContain('Default (');
    expect($html)->toContain('aria-label="'.e(__('admin.users.limit_save')).'"');
});

it('shows the own limit in the field and the default only as placeholder', function () {
    $u = seedUsers()['old'];
    $u->update(['address_limit' => 50]);
    Setting::put('address_limit', 120);
    $html = $this->get(route('admin.users.index'))->getContent();

    expect($html)->toContain('value="50"')->toContain('placeholder="120"');
});

it('moves the global limit to its own settings page', function () {
    Setting::put('address_limit', 120);
    $users = $this->get(route('admin.users.index'))->getContent();
    expect($users)->not->toContain('action="'.route('admin.settings.update').'"');

    $this->get(route('admin.settings.edit'))->assertOk()->assertSee('value="120"', false)->assertSee(route('admin.settings.update'), false);
    $this->patch(route('admin.settings.update'), ['address_limit' => 90])->assertRedirect(route('admin.settings.edit'));
    expect(Setting::getInt('address_limit', 0))->toBe(90);
    $this->patch(route('admin.settings.update'), ['address_limit' => 0])->assertSessionHasErrors('address_limit');
});

it('links the settings page in the admin sub navigation', function () {
    foreach (['admin.users.index', 'admin.domains.index', 'admin.settings.edit'] as $route) {
        $this->get(route($route))->assertOk()->assertSee(route('admin.settings.edit'), false);
    }
});

it('keeps the settings page away from non admins', function () {
    auth()->logout();
    $this->actingAs(User::factory()->create())->get(route('admin.settings.edit'))->assertForbidden();
});

it('shows the sign-up date localized with the language of the page', function () {
    User::factory()->create(['email' => 'datum@schule.example.org', 'created_at' => Carbon::parse('2026-03-07 10:00:00', 'Europe/Berlin')]);

    $this->get(route('admin.users.index'), ['Accept-Language' => 'de'])->assertSee('07.03.2026')->assertDontSee('2026-03-07');
    $this->get(route('admin.users.index'), ['Accept-Language' => 'en'])->assertSee('03/07/2026');
});
