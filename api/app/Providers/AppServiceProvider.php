<?php

namespace App\Providers;

use App\Modules\Auth\Domain\Channels\InternalLoginChannel;
use App\Modules\Auth\Domain\Channels\InternalRegisterChannel;
use App\Modules\Auth\Domain\Services\LoginService;
use App\Modules\Auth\Domain\Services\RegisterService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginService::class, function ($app) {
            return new LoginService([
                'internal' => $app->make(InternalLoginChannel::class),
            ]);
        });

        $this->app->singleton(RegisterService::class, function ($app) {
            return new RegisterService([
                'internal' => $app->make(InternalRegisterChannel::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
