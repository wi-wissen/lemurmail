<?php

use App\Models\Address;
use App\Services\PinIssuer;
use App\Support\Clock;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

const MB_DOMAIN = 'tmp-mail.example.org';

function loginAs($test, string $address, string $pin, array $extra = [])
{
    return $test->from('/')->post(route('mailbox.login'), ['address' => $address, 'pin' => $pin] + $extra);
}

beforeEach(fn () => RateLimiter::clear('x'));

it('shows the login page on the home route', function () {
    $this->get('/')->assertOk()->assertSee(__('mailbox.login_title'));
    expect(route('home', absolute: false))->toBe('/');
});

it('logs in with address and PIN, with or without domain, any case and spacing', function (string $addr, string $pin) {
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);

    loginAs($this, $addr, $pin)->assertRedirect(route('mailbox.inbox'));
    $this->get(route('mailbox.inbox'))->assertOk()->assertSee('klasse7a-03@'.MB_DOMAIN);
})->with([
    ['klasse7a-03@'.MB_DOMAIN, 'K7M4QX9R'],
    ['klasse7a-03', 'K7M4 QX9R'],
    ['Klasse7A-03@'.strtoupper(MB_DOMAIN), 'k7m4-qx9r'],
    ['  klasse7a-03 ', ' k7m4qx9r '],
]);

it('gives the identical error for unknown address, wrong PIN, expired PIN and foreign domain', function () {
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    Address::factory()->withPin('T3WD8HNP', Clock::now() - 5)->create(['local_part' => 'abgelaufen']);
    Address::factory()->create(['local_part' => 'ohnepin']);

    $messages = collect([
        ['klasse7a-99', 'K7M4QX9R'],
        ['klasse7a-03', 'AAAAAAAA'],
        ['abgelaufen', 'T3WD8HNP'],
        ['ohnepin', 'T3WD8HNP'],
        ['klasse7a-03@evil.example', 'K7M4QX9R'],
    ])->map(function ($c) {
        $r = loginAs($this, ...$c);
        $r->assertRedirect('/')->assertSessionHasErrors('address');
        $this->assertGuest();

        return session('errors')->first('address');
    });

    expect($messages->unique())->toHaveCount(1)->and($messages->first())->toBe(__('mailbox.login_failed'));
});

/** Starts a fresh browser session (new cookie) while keeping the client IP. */
function newBrowserSession($test): void
{
    $test->flushSession();
    session()->setId(Str::random(40));
}

it('blocks a browser session after 5 failures, even for the right PIN, without revealing whether the address exists', function (string $local) {
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);

    for ($i = 0; $i < 5; $i++) {
        loginAs($this, $local, 'AAAAAAAA')->assertSessionHasErrors('address');
    }
    loginAs($this, $local, 'K7M4QX9R')->assertSessionHasErrors('address');

    expect(session('errors')->first('address'))->toBe(__('mailbox.too_many_attempts'));
    $this->get(route('mailbox.inbox'))->assertRedirect(route('home'));
})->with(['klasse7a-03', 'does-not-exist']);

it('does not let one browser session block the other students on the same network', function () {
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    Address::factory()->withPin('T3WD8HNP')->create(['local_part' => 'klasse7a-04']);

    foreach (range(1, 8) as $i) { // one pupil hammering away on a school-wide IP
        $last = loginAs($this, 'klasse7a-03', 'AAAAAAAA');
    }
    $last->assertSessionHasErrors(['address' => __('mailbox.too_many_attempts')]);

    newBrowserSession($this);
    loginAs($this, 'klasse7a-04', 'T3WD8HNP')->assertRedirect(route('mailbox.inbox'));

    newBrowserSession($this);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R')->assertRedirect(route('mailbox.inbox')); // even the attacked address still works
});

it('stops a brute force on one address across many browser sessions', function () {
    config(['lemurmail.pin_address_max_attempts' => 6]);
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);

    foreach (range(1, 6) as $i) {
        newBrowserSession($this);
        loginAs($this, 'klasse7a-03', 'AAAAAAAA')->assertSessionHasErrors('address');
    }
    newBrowserSession($this);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R')->assertSessionHasErrors('address');
    expect(session('errors')->first('address'))->toBe(__('mailbox.too_many_attempts'));
});

it('does not limit by IP address unless that is switched on', function () {
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    expect(config('lemurmail.pin_ip_max_attempts'))->toBe(0);

    foreach (range(1, 40) as $i) {
        newBrowserSession($this);
        loginAs($this, "nobody-$i", 'AAAAAAAA');
    }
    newBrowserSession($this);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R')->assertRedirect(route('mailbox.inbox'));
});

it('limits by IP address when configured', function () {
    config(['lemurmail.pin_ip_max_attempts' => 6]);
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);

    foreach (range(1, 6) as $i) {
        newBrowserSession($this);
        loginAs($this, "nobody-$i", 'AAAAAAAA');
    }
    newBrowserSession($this);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R')->assertSessionHasErrors('address');
    expect(session('errors')->first('address'))->toBe(__('mailbox.too_many_attempts'));
});

it('does not count successful logins against the limit', function () {
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    foreach (range(1, 8) as $i) {
        newBrowserSession($this);
        loginAs($this, 'klasse7a-03', 'K7M4QX9R')->assertRedirect(route('mailbox.inbox'));
    }
});

it('requires the captcha when enabled', function () {
    config(['friendlycaptcha.enabled' => true, 'friendlycaptcha.secret' => 's', 'friendlycaptcha.sitekey' => 'k']);
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);

    loginAs($this, 'klasse7a-03', 'K7M4QX9R')->assertSessionHasErrors('frc-captcha-solution');

    Http::fake(['*' => Http::response(['success' => true])]);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R', ['frc-captcha-solution' => 'abc'])->assertRedirect(route('mailbox.inbox'));
});

it('shows the captcha widget on the login page when enabled', function () {
    config(['friendlycaptcha.enabled' => true, 'friendlycaptcha.sitekey' => 'SITEKEY123']);
    $this->get('/')->assertSee('frc-captcha', false)->assertSee('SITEKEY123', false);
});

it('ends the session when the PIN expires', function () {
    Address::factory()->withPin('K7M4QX9R', Clock::now() + 600)->create(['local_part' => 'klasse7a-03']);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R');
    $this->get(route('mailbox.inbox'))->assertOk();

    $this->travel(11)->minutes();

    $this->get(route('mailbox.inbox'))->assertRedirect(route('home'));
    $this->getJson(route('mailbox.messages'))->assertStatus(401);
});

it('ends the session when the PIN is replaced', function () {
    $address = Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R');
    app(PinIssuer::class)->issue($address, Clock::now() + 3600);

    $this->get(route('mailbox.inbox'))->assertRedirect(route('home'));
});

it('ends the session when the lesson is ended', function () {
    $address = Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R');
    app(PinIssuer::class)->revoke($address, Clock::now());

    $this->get(route('mailbox.inbox'))->assertRedirect(route('home'));
});

it('logs out', function () {
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R');
    $this->post(route('mailbox.logout'))->assertRedirect(route('home'));
    $this->get(route('mailbox.inbox'))->assertRedirect(route('home'));
});

it('redirects a logged-in student from home to the inbox', function () {
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R');
    $this->get('/')->assertRedirect(route('mailbox.inbox'));
});

it('keeps a student session out of teacher routes', function () {
    Address::factory()->withPin('K7M4QX9R')->create(['local_part' => 'klasse7a-03']);
    loginAs($this, 'klasse7a-03', 'K7M4QX9R');
    $this->get(route('groups.index'))->assertRedirect(route('login'));
});
