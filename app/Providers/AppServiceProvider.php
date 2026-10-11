<?php

namespace App\Providers;

use App\Listeners\NotifyAdminOfPendingTeacher;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
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
    }
}
