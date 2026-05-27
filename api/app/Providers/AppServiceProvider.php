<?php

namespace App\Providers;

use App\Modules\Auth\Domain\Channels\InternalLoginChannel;
use App\Modules\Auth\Domain\Channels\InternalRegisterChannel;
use App\Modules\Auth\Domain\Services\LoginService;
use App\Modules\Auth\Domain\Services\RegisterService;
use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Lead\Domain\Models\Lead;
use Illuminate\Support\Facades\Route;
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

        $this->app->scoped(CurrentCompany::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('lead', function (string $value) {
            $companyId = auth()->user()?->company_id;

            if ($companyId === null) {
                abort(403);
            }

            return Lead::query()
                ->where('company_id', $companyId)
                ->findOrFail($value);
        });

        Route::bind('conversation', function (string $value) {
            $companyId = auth()->user()?->company_id;

            if ($companyId === null) {
                abort(403);
            }

            return Conversation::query()
                ->where('company_id', $companyId)
                ->findOrFail($value);
        });
    }
}
