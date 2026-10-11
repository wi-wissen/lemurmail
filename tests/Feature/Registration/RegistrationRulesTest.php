<?php

use App\Models\BlockedDomain;
use App\Models\OrganizationDomain;
use App\Models\User;
use App\Notifications\TeacherAwaitsApproval;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

function register($test, string $email, array $extra = [])
{
    return $test->from('/register')->post('/register', ['email' => $email, 'password' => 'a-long-password-1'] + $extra);
}

it('ships a default list of freemail domains', function () {
    expect(BlockedDomain::where('domain', 'gmail.com')->exists())->toBeTrue()
        ->and(BlockedDomain::count())->toBeGreaterThan(25);
});

it('shows the registration form with email and password only', function () {
    $this->get('/register')->assertOk()->assertSee('name="email"', false)->assertSee('name="password"', false)
        ->assertDontSee('name="name"', false)->assertDontSee('password_confirmation', false);
});

it('rejects freemail domains with the hint to use an organisation address', function () {
    register($this, 'lehrer@gmail.com')->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe(__('auth.freemail_not_allowed'));
    register($this, 'x@mail.gmail.com')->assertSessionHasErrors('email');
    expect(User::count())->toBe(0);
});

it('rejects the services own mailbox domain', function () {
    register($this, 'klasse7a-01@tmp-mail.example.org')->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe(__('auth.own_domain_not_allowed'));
    expect(User::count())->toBe(0);
});

it('rejects invalid addresses and weak passwords', function () {
    register($this, 'not-an-email')->assertSessionHasErrors('email');
    $this->post('/register', ['email' => 'a@schule.example.org', 'password' => 'short'])->assertSessionHasErrors('password');
});

it('registers an approved-domain teacher as active, pending verification, and sends the verification mail', function () {
    Notification::fake();
    OrganizationDomain::create(['domain' => 'schule.example.org']);

    register($this, 'Lehrer@Schule.Example.org')->assertRedirect(route('verification.notice'));

    $u = User::sole();
    expect($u->email)->toBe('lehrer@schule.example.org')->and($u->status)->toBe('active')->and($u->role)->toBe('teacher')
        ->and($u->email_verified_at)->toBeNull()->and($u->name)->toBe('lehrer');
    $this->assertAuthenticatedAs($u);
    Notification::assertSentTo($u, VerifyEmail::class);
});

it('registers an unknown-domain teacher as pending', function () {
    register($this, 'lehrer@andere-schule.example')->assertRedirect(route('verification.notice'));
    expect(User::sole()->status)->toBe('pending');
});

it('notifies the admin only after a pending teacher verified the address', function () {
    Notification::fake();
    config(['lemurmail.admin_email' => 'admin@verein.example']);
    $admin = User::factory()->superAdmin()->create();
    $u = User::factory()->pending()->unverified()->create(['email' => 'lehrer@andere-schule.example']);

    Event::dispatch(new Registered($u));
    Notification::assertNotSentTo($admin, TeacherAwaitsApproval::class);
    Notification::assertSentOnDemandTimes(TeacherAwaitsApproval::class, 0);

    $u->markEmailAsVerified();
    Event::dispatch(new Verified($u));

    Notification::assertSentOnDemand(TeacherAwaitsApproval::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'admin@verein.example');
    Notification::assertSentTo($admin, TeacherAwaitsApproval::class);
});

it('does not notify the admin for approved-domain teachers', function () {
    Notification::fake();
    $u = User::factory()->unverified()->create(['status' => 'active']);
    $u->markEmailAsVerified();
    Event::dispatch(new Verified($u));
    Notification::assertNothingSent();
});

it('shows a waiting page to pending teachers and a reason to blocked or rejected ones', function () {
    foreach (['pending', 'rejected', 'blocked'] as $status) {
        $u = User::factory()->create(['status' => $status]);
        $this->actingAs($u)->get(route('account.pending'))->assertOk()->assertSee(__('account.status_'.$status.'_title'));
    }
    $this->actingAs(User::factory()->create())->get(route('account.pending'))->assertRedirect(route('groups.index'));
});

it('lets an unverified teacher verify but not use the app', function () {
    $this->actingAs(User::factory()->unverified()->create())->get(route('groups.index'))->assertRedirect(route('verification.notice'));
});

it('requires the captcha for registration and password reset when enabled', function () {
    config(['friendlycaptcha.enabled' => true, 'friendlycaptcha.secret' => 's', 'friendlycaptcha.sitekey' => 'k']);
    OrganizationDomain::create(['domain' => 'schule.example.org']);

    register($this, 'lehrer@schule.example.org')->assertSessionHasErrors('frc-captcha-solution');
    $this->post('/forgot-password', ['email' => 'lehrer@schule.example.org'])->assertSessionHasErrors('frc-captcha-solution');

    Http::fake(['*' => Http::sequence()
        ->push(['success' => false, 'errors' => ['solution_invalid']])
        ->push(['success' => true])]);
    register($this, 'lehrer@schule.example.org', ['frc-captcha-solution' => 'bad'])->assertSessionHasErrors('frc-captcha-solution');
    expect(User::count())->toBe(0);

    register($this, 'lehrer@schule.example.org', ['frc-captcha-solution' => 'good'])->assertRedirect(route('verification.notice'));
    Http::assertSent(fn ($req) => $req['solution'] === 'good' && $req['secret'] === 's' && $req['sitekey'] === 'k');
});

it('accepts the registration when the captcha service itself is down', function () {
    config(['friendlycaptcha.enabled' => true]);
    Http::fake(['*' => Http::response('', 503)]);
    register($this, 'lehrer@andere-schule.example', ['frc-captcha-solution' => 'x'])->assertRedirect(route('verification.notice'));
});

it('renders the captcha widget on register, login-reset and mailbox pages when enabled', function () {
    config(['friendlycaptcha.enabled' => true, 'friendlycaptcha.sitekey' => 'SITEKEY123']);
    $this->get('/register')->assertSee('SITEKEY123', false);
    $this->get('/forgot-password')->assertSee('SITEKEY123', false);
});

it('creates a verified super admin via the artisan command', function () {
    $this->artisan('lemurmail:make-admin', ['email' => 'admin@verein.example', '--password' => 'a-long-password-1'])->assertSuccessful();
    $u = User::where('email', 'admin@verein.example')->sole();
    expect($u->isSuperAdmin())->toBeTrue()->and($u->isActive())->toBeTrue()->and($u->email_verified_at)->not->toBeNull();
    $this->artisan('lemurmail:make-admin', ['email' => 'admin@verein.example', '--password' => 'x'])->assertFailed();
});
