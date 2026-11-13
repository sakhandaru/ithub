<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
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
        $this->configureDefaults();

        Route::bind('workspace', function (string $value): Workspace {
            $user = request()->user();

            abort_unless($user !== null, 404);

            return Workspace::query()->visibleTo($user)->findOrFail($value);
        });

        Route::bind('project', function (string $value): Project {
            $user = request()->user();
            $workspace = request()->route('workspace');

            abort_unless($user !== null && $workspace instanceof Workspace, 404);

            return Project::query()
                ->whereBelongsTo($workspace)
                ->visibleTo($user)
                ->findOrFail($value);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
