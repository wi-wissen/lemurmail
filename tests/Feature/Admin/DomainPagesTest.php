<?php

use App\Models\BlockedDomain;
use App\Models\OrganizationDomain;
use App\Models\User;

beforeEach(fn () => $this->actingAs(User::factory()->superAdmin()->create()));

it('shows four tabs on every admin page', function () {
    foreach (['admin.users.index', 'admin.domains.index', 'admin.freemail.index', 'admin.settings.edit'] as $route) {
        $html = $this->get(route($route))->assertOk()->getContent();
        foreach (['admin.users.index', 'admin.domains.index', 'admin.freemail.index', 'admin.settings.edit'] as $target) {
            expect($html)->toContain(route($target));
        }
    }
});

it('keeps the blocked domains off the school domains page', function () {
    BlockedDomain::create(['domain' => 'sperrliste.example']);
    OrganizationDomain::create(['domain' => 'schule.example.org']);

    $this->get(route('admin.domains.index'))->assertOk()->assertSee('schule.example.org')->assertDontSee('sperrliste.example')->assertDontSee('gmail.com');
    $this->get(route('admin.freemail.index'))->assertOk()->assertSee('sperrliste.example')->assertSee('gmail.com')->assertDontSee('schule.example.org');
});

it('maintains the blocked domains on their own page', function () {
    $this->post(route('admin.freemail.store'), ['domain' => ' @Wegwerf.Example '])->assertRedirect();
    $this->post(route('admin.freemail.store'), ['domain' => 'wegwerf.example'])->assertRedirect(); // duplicate ignored
    expect(BlockedDomain::where('domain', 'wegwerf.example')->count())->toBe(1);
    $this->post(route('admin.freemail.store'), ['domain' => 'no domain'])->assertSessionHasErrors('domain');

    $this->get(route('admin.freemail.index'))->assertSee('wegwerf.example');
    $this->delete(route('admin.freemail.destroy', BlockedDomain::where('domain', 'wegwerf.example')->first()))->assertRedirect();
    expect(BlockedDomain::where('domain', 'wegwerf.example')->exists())->toBeFalse();
});

it('lists the blocked domains one below the other, not in columns', function () {
    $html = $this->get(route('admin.freemail.index'))->getContent();
    expect($html)->not->toContain('columns-2')->not->toContain('columns-3')->not->toContain('sm:grid-cols-2');
});

it('adds school domains with an optional pattern for the name before the @', function () {
    $this->post(route('admin.domains.organization.store'), ['domain' => 'Schule.Example.org', 'pattern' => '[a-z]+\.[a-z]+'])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('admin.domains.organization.store'), ['domain' => 'andere.example'])->assertRedirect()->assertSessionHasNoErrors();

    $rows = OrganizationDomain::orderBy('domain')->get(['domain', 'local_part_pattern'])->toArray();
    expect($rows)->toBe([
        ['domain' => 'andere.example', 'local_part_pattern' => null],
        ['domain' => 'schule.example.org', 'local_part_pattern' => '[a-z]+\.[a-z]+'],
    ]);

    $this->get(route('admin.domains.index'))->assertSee('schule.example.org')->assertSee('[a-z]+\.[a-z]+', false)->assertSee(__('admin.domains.pattern_all'));
});

it('allows several patterns for one domain but ignores exact duplicates', function () {
    foreach (['[a-z]+\.[a-z]+', 'verwaltung', 'verwaltung'] as $pattern) {
        $this->post(route('admin.domains.organization.store'), ['domain' => 'schule.example.org', 'pattern' => $pattern])->assertSessionHasNoErrors();
    }
    $this->post(route('admin.domains.organization.store'), ['domain' => 'schule.example.org'])->assertSessionHasNoErrors();
    $this->post(route('admin.domains.organization.store'), ['domain' => 'schule.example.org', 'pattern' => ''])->assertSessionHasNoErrors();

    expect(OrganizationDomain::where('domain', 'schule.example.org')->count())->toBe(3);
});

it('rejects an invalid pattern with a message on the pattern field', function (string $pattern) {
    $this->post(route('admin.domains.organization.store'), ['domain' => 'schule.example.org', 'pattern' => $pattern])->assertSessionHasErrors('pattern');
    expect(OrganizationDomain::count())->toBe(0);
})->with(['[a-z', '(', str_repeat('a', 201)]);

it('removes a school domain entry', function () {
    $entry = OrganizationDomain::create(['domain' => 'schule.example.org', 'local_part_pattern' => 'lehrer']);
    OrganizationDomain::create(['domain' => 'schule.example.org', 'local_part_pattern' => 'verwaltung']);

    $this->delete(route('admin.domains.organization.destroy', $entry))->assertRedirect();
    expect(OrganizationDomain::pluck('local_part_pattern')->all())->toBe(['verwaltung']);
});

it('uses the pattern when classifying registrations', function () {
    auth()->logout();
    OrganizationDomain::create(['domain' => 'schule.example.org', 'local_part_pattern' => '[a-z]+\.[a-z]+']);

    $this->post('/register', ['email' => 'max.mustermann@schule.example.org', 'password' => 'a-long-password-1']);
    expect(User::where('email', 'max.mustermann@schule.example.org')->value('status'))->toBe('active');

    auth()->logout();
    $this->post('/register', ['email' => 'kind.mustermann7@schule.example.org', 'password' => 'a-long-password-1']);
    expect(User::where('email', 'kind.mustermann7@schule.example.org')->value('status'))->toBe('pending');
});

it('keeps old entries without a pattern working for the whole domain', function () {
    auth()->logout();
    OrganizationDomain::create(['domain' => 'schule.example.org']);
    $this->post('/register', ['email' => 'egal@schule.example.org', 'password' => 'a-long-password-1']);
    expect(User::where('email', 'egal@schule.example.org')->value('status'))->toBe('active');
});
