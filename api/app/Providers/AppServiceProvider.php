<?php

namespace App\Providers;

use App\Modules\Auth\Domain\Channels\InternalLoginChannel;
use App\Modules\Auth\Domain\Channels\InternalRegisterChannel;
use App\Modules\Auth\Domain\Services\LoginService;
use App\Modules\Auth\Domain\Services\RegisterService;
use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use App\Modules\Lead\Domain\Models\Lead;
use App\Modules\Lead\Domain\Models\PipelineStage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Core\Logging\Contracts\LogDriver;
use App\Core\Logging\Drivers\HorusLogDriver;
use App\Core\Logging\Drivers\NullLogDriver;

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

        $this->app->singleton(LogDriver::class, function () {
            $enabled = (bool) config('horus.enabled', true);
            $driver = (string) config('horus.driver', 'horus');

            if (! $enabled || $driver !== 'horus') {
                return new NullLogDriver;
            }

            return new HorusLogDriver;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('lead', function (string $value) {
            /** @var \App\Models\User|null $user */
            $user = request()->user();
            $companyId = $user?->company_id;

            if ($companyId === null) {
                abort(403);
            }

            return Lead::query()
                ->where('company_id', $companyId)
                ->findOrFail($value);
        });

        Route::bind('conversation', function (string $value) {
            /** @var \App\Models\User|null $user */
            $user = request()->user();
            $companyId = $user?->company_id;

            if ($companyId === null) {
                abort(403);
            }

            return Conversation::query()
                ->where('company_id', $companyId)
                ->findOrFail($value);
        });

        Route::bind('knowledgeSource', function (string $value) {
            /** @var \App\Models\User|null $user */
            $user = request()->user();
            $companyId = $user?->company_id;

            if ($companyId === null) {
                abort(403);
            }

            return KnowledgeSource::query()
                ->where('company_id', $companyId)
                ->findOrFail($value);
        });

        Route::bind('stage', function (string $value) {
            /** @var \App\Models\User|null $user */
            $user = request()->user();
            $companyId = $user?->company_id;

            if ($companyId === null) {
                abort(403);
            }

            return PipelineStage::query()
                ->where('company_id', $companyId)
                ->findOrFail($value);
        });
    }
}
