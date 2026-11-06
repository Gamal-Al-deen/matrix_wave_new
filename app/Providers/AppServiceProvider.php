<?php

namespace App\Providers;

use App\Services\Notifications\LoginNotifier;
use App\Services\Notifications\RegistrationNotifier;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
        Event::listen(Registered::class, RegistrationNotifier::class);
        Event::listen(Login::class, LoginNotifier::class);
    }
}
