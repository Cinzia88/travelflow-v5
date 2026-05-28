<?php

namespace App\Providers;

use App\Models\Email;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;


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
        Email::observe(\App\Observers\EmailObserver::class);
        Model::unguard();
    }
}
