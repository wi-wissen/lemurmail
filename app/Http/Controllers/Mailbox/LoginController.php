<?php

namespace App\Http\Controllers\Mailbox;

use App\Core\Address\LocalPart;
use App\Core\Pin\PinFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mailbox\LoginRequest;
use App\Models\Address;
use App\Support\Clock;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    private static ?string $dummyHash = null;

    public function store(LoginRequest $request, RateLimiter $limiter): RedirectResponse
    {
        [$local, $domainMatches] = $this->splitAddress((string) $request->input('address', ''));
        $ip = $request->ip();
        $addressKey = 'mailbox-login:'.sha1($local.'|'.$ip);
        $ipKey = 'mailbox-login-ip:'.$ip;

        if ($limiter->tooManyAttempts($addressKey, config('lemurmail.pin_max_attempts'))
            || $limiter->tooManyAttempts($ipKey, config('lemurmail.pin_ip_max_attempts'))) {
            return $this->failed(__('mailbox.too_many_attempts'));
        }

        $address = $domainMatches && LocalPart::isValid($local)
            ? Address::where('local_part', $local)->first()
            : null;

        // Always hash, also for unknown addresses, so that the response time does not reveal them.
        $pinMatches = Hash::check(
            PinFormat::normalize((string) $request->input('pin', '')),
            $address?->pin_hash ?: self::dummyHash(),
        );

        if ($address !== null && $pinMatches && $address->pinIsValid(Clock::now())) {
            $request->session()->regenerate();
            $request->session()->put([
                'mailbox.address_id' => $address->id,
                'mailbox.fingerprint' => $address->pinFingerprint(),
            ]);
            $limiter->clear($addressKey);

            return redirect()->route('mailbox.inbox');
        }

        $decay = config('lemurmail.pin_decay_seconds');
        $limiter->hit($addressKey, $decay);
        $limiter->hit($ipKey, $decay);

        return $this->failed(__('mailbox.login_failed'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['mailbox.address_id', 'mailbox.fingerprint']);

        return redirect()->route('home');
    }

    /**
     * Accepts the full address or only the local part. Returns the normalized local part and
     * whether the given domain (if any) is the mail domain.
     *
     * @return array{0: string, 1: bool}
     */
    private function splitAddress(string $input): array
    {
        $normalized = LocalPart::normalize($input);

        if (! str_contains($normalized, '@')) {
            return [$normalized, true];
        }

        [$local, $domain] = explode('@', $normalized, 2);

        return [$local, $domain === strtolower((string) config('lemurmail.mail_domain'))];
    }

    private function failed(string $message): RedirectResponse
    {
        return back()->withErrors(['address' => $message])->onlyInput('address');
    }

    private static function dummyHash(): string
    {
        return self::$dummyHash ??= Hash::make(Str::random(32));
    }
}
