<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Milestone;
use App\Models\Module;
use App\Models\Project;
use App\Models\SavedView;
use App\Models\Task;
use App\Models\TaskRelation;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

        Relation::morphMap([
            'project' => Project::class,
            'task' => Task::class,
            'workspace' => Workspace::class,
        ]);

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->isAuditor() && ! in_array($ability, ['view', 'viewAny', 'download'], true)) {
                return false;
            }

            return null;
        });

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

        Route::bind('task', function (string $value): Task {
            $user = request()->user();
            $project = request()->route('project');

            abort_unless($user !== null && $project instanceof Project, 404);

            return Task::query()
                ->whereBelongsTo($project)
                ->visibleTo($user)
                ->findOrFail($value);
        });

        Route::bind('milestone', function (string $value): Milestone {
            $project = request()->route('project');
            abort_unless($project instanceof Project, 404);

            return $project->milestones()->findOrFail($value);
        });

        Route::bind('module', function (string $value): Module {
            $project = request()->route('project');
            abort_unless($project instanceof Project, 404);

            return $project->modules()->findOrFail($value);
        });

        Route::bind('relation', function (string $value): TaskRelation {
            $task = request()->route('task');
            abort_unless($task instanceof Task, 404);

            return $task->relations()->findOrFail($value);
        });

        Route::bind('comment', function (string $value): Comment {
            $task = request()->route('task');
            abort_unless($task instanceof Task, 404);

            return $task->comments()->findOrFail($value);
        });

        Route::bind('attachment', function (string $value): Attachment {
            $task = request()->route('task');
            if ($task instanceof Task) {
                return $task->attachments()->findOrFail($value);
            }

            $project = request()->route('project');
            abort_unless($project instanceof Project, 404);

            return $project->attachments()->findOrFail($value);
        });

        Route::bind('savedView', function (string $value): SavedView {
            $project = request()->route('project');
            abort_unless($project instanceof Project, 404);

            return $project->savedViews()->whereKey($value)->findOrFail($value);
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
