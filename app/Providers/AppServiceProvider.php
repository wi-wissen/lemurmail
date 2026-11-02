<?php

namespace App\Providers;

use App\Listeners\NotifyAdminOfPendingTeacher;
use App\Mail\LettermintTransport;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(10));

        Event::listen(Verified::class, NotifyAdminOfPendingTeacher::class);

        Mail::extend('lettermint', fn (array $config) => new LettermintTransport(
            (string) ($config['token'] ?? ''),
            (string) ($config['endpoint'] ?? 'https://api.lettermint.co/v1'),
            (int) ($config['timeout'] ?? 10),
        ));
    }
}
