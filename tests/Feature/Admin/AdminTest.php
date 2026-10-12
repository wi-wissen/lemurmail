<?php

use App\Models\OrganizationDomain;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AccountDecision;
use Illuminate\Support\Facades\Notification;

function admin(): User
{
    return User::factory()->superAdmin()->create();
}

it('keeps everyone but the super admin out', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('admin.users.index'))->assertForbidden();
    $this->post(route('admin.users.approve', User::factory()->pending()->create()))->assertForbidden();
    $this->get(route('admin.domains.index'))->assertForbidden();
});

it('lists teachers with pending ones first', function () {
    User::factory()->create(['email' => 'aktiv@schule.example.org']);
    User::factory()->pending()->create(['email' => 'wartet@andere.example']);
    $html = $this->actingAs(admin())->get(route('admin.users.index'))->assertOk()->assertSee('wartet@andere.example')->assertSee('aktiv@schule.example.org')->getContent();
    expect(strpos($html, 'wartet@andere.example'))->toBeLessThan(strpos($html, 'aktiv@schule.example.org'));
});

it('approves and rejects pending teachers and tells them by mail', function () {
    Notification::fake();
    $a = User::factory()->pending()->create();
    $b = User::factory()->pending()->create();

    $this->actingAs(admin())->post(route('admin.users.approve', $a))->assertRedirect();
    $this->post(route('admin.users.reject', $b))->assertRedirect();

    expect($a->fresh()->status)->toBe('active')->and($b->fresh()->status)->toBe('rejected');
    Notification::assertSentTo($a, AccountDecision::class, fn ($n) => $n->approved === true);
    Notification::assertSentTo($b, AccountDecision::class, fn ($n) => $n->approved === false);
});

it('blocks and unblocks active teachers', function () {
    $t = User::factory()->create();
    $this->actingAs(admin())->post(route('admin.users.block', $t))->assertRedirect();
    expect($t->fresh()->status)->toBe('blocked');
    $this->actingAs($t->fresh())->get(route('groups.index'))->assertRedirect(route('account.pending'));
    $this->actingAs(admin())->post(route('admin.users.unblock', $t))->assertRedirect();
    expect($t->fresh()->status)->toBe('active');
});

it('refuses state changes that make no sense', function () {
    $active = User::factory()->create();
    $this->actingAs(admin())->post(route('admin.users.approve', $active))->assertSessionHasErrors();
    $other = admin();
    $this->post(route('admin.users.block', $other))->assertSessionHasErrors(); // never block a super admin
    expect($other->fresh()->status)->toBe('active');
});

it('sets and clears the address cap of a teacher and the global cap', function () {
    $t = User::factory()->create();
    $this->actingAs(admin())->patch(route('admin.users.limit', $t), ['address_limit' => 50])->assertRedirect();
    expect($t->fresh()->address_limit)->toBe(50)->and($t->fresh()->addressLimit())->toBe(50);
    $this->patch(route('admin.users.limit', $t), ['address_limit' => ''])->assertRedirect();
    expect($t->fresh()->address_limit)->toBeNull();
    $this->patch(route('admin.users.limit', $t), ['address_limit' => -1])->assertSessionHasErrors('address_limit');

    $this->patch(route('admin.settings.update'), ['address_limit' => 120])->assertRedirect();
    expect(Setting::getInt('address_limit', 0))->toBe(120)->and($t->fresh()->addressLimit())->toBe(120);
});

it('maintains organisation domains', function () {
    $this->actingAs(admin())->post(route('admin.domains.organization.store'), ['domain' => ' @Schule.Example.org '])->assertRedirect();
    $this->post(route('admin.domains.organization.store'), ['domain' => 'schule.example.org'])->assertRedirect(); // duplicate ignored
    expect(OrganizationDomain::pluck('domain')->all())->toBe(['schule.example.org']);
    $this->post(route('admin.domains.organization.store'), ['domain' => 'not a domain'])->assertSessionHasErrors('domain');

    $this->get(route('admin.domains.index'))->assertOk()->assertSee('schule.example.org');

    $this->delete(route('admin.domains.organization.destroy', OrganizationDomain::first()))->assertRedirect();
    expect(OrganizationDomain::count())->toBe(0);
});

it('uses the maintained lists for new registrations', function () {
    OrganizationDomain::create(['domain' => 'neu.example.org']);
    $this->post('/register', ['email' => 'x@neu.example.org', 'password' => 'a-long-password-1']);
    expect(User::where('email', 'x@neu.example.org')->value('status'))->toBe('active');
});
