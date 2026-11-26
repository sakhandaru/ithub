<?php

namespace Database\Seeders;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Enums\TaskPriority;
use App\Enums\TaskRelationType;
use App\Enums\TaskStatus;
use App\Enums\WorkspaceRole;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Milestone;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Release;
use App\Models\SavedView;
use App\Models\Task;
use App\Models\TaskRelation;
use App\Models\Unit;
use App\Models\UnitMembership;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->get()->keyBy('email');
        $admin = $users->first(fn (User $user): bool => $user->isAdmin()) ?? $users->first();

        if (! $admin) {
            $this->command?->error('Fresh demo data membutuhkan setidaknya satu user.');

            return;
        }

        foreach ([
            ['name' => 'Platform Engineering', 'timezone' => 'Asia/Jakarta'],
            ['name' => 'Product Operations', 'timezone' => 'Asia/Jakarta'],
        ] as $workspaceAttributes) {
            $workspace = Workspace::query()->firstOrCreate(
                ['name' => $workspaceAttributes['name']],
                ['timezone' => $workspaceAttributes['timezone'], 'created_by_id' => $admin->id],
            );

            if ($workspace->name === 'Platform Engineering') {
                $activeUnit = Unit::query()->whereBelongsTo($workspace, 'workspace')->first();

                foreach ($users as $user) {
                    $user->forceFill([
                        'active_workspace_id' => $workspace->id,
                        'active_unit_id' => $activeUnit?->id,
                    ])->save();
                }
            }

            $this->seedWorkspace($workspace, $users, $admin);
        }
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedWorkspace(Workspace $workspace, Collection $users, User $admin): void
    {
        $workspaceRoles = [
            'saka@example.com' => WorkspaceRole::Owner,
            'wifki@example.com' => WorkspaceRole::WorkspaceAdmin,
            'rizki@example.com' => WorkspaceRole::Member,
            'yudha@example.com' => WorkspaceRole::Guest,
        ];

        foreach ($users as $user) {
            WorkspaceMembership::query()->firstOrCreate(
                ['workspace_id' => $workspace->id, 'user_id' => $user->id],
                [
                    'role' => $workspaceRoles[$user->email] ?? WorkspaceRole::Member,
                    'invited_by_id' => $admin->id,
                    'joined_at' => now()->subDays(fake()->numberBetween(5, 120)),
                ],
            );
        }

        $unitNames = $workspace->name === 'Platform Engineering'
            ? ['Development', 'Infrastructure', 'Security', 'Quality Assurance']
            : ['Customer Success', 'Operations', 'Finance'];

        $units = collect($unitNames)->mapWithKeys(function (string $name) use ($workspace, $admin): array {
            $unit = Unit::query()->firstOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $name],
                ['created_by_id' => $admin->id],
            );

            return [$name => $unit];
        });

        foreach ($units->values() as $index => $unit) {
            foreach ($users->values()->slice(0, max(4, $users->count() - $index)) as $user) {
                UnitMembership::query()->firstOrCreate(
                    ['unit_id' => $unit->id, 'user_id' => $user->id],
                    ['added_by_id' => $admin->id],
                );
            }
        }

        $projectDefinitions = $workspace->name === 'Platform Engineering'
            ? [
                ['name' => 'Portal Redesign', 'identifier' => 'WEB', 'unit' => 'Development', 'status' => ProjectStatus::Active, 'visibility' => ProjectVisibility::Workspace],
                ['name' => 'Mobile Release 2.4', 'identifier' => 'MOB', 'unit' => 'Development', 'status' => ProjectStatus::Active, 'visibility' => ProjectVisibility::Unit],
                ['name' => 'Kubernetes Cluster Upgrade', 'identifier' => 'INFRA', 'unit' => 'Infrastructure', 'status' => ProjectStatus::Active, 'visibility' => ProjectVisibility::Unit],
                ['name' => 'VPN Hardening', 'identifier' => 'SEC', 'unit' => 'Security', 'status' => ProjectStatus::Active, 'visibility' => ProjectVisibility::Private],
                ['name' => 'Regression Automation', 'identifier' => 'QA', 'unit' => 'Quality Assurance', 'status' => ProjectStatus::Draft, 'visibility' => ProjectVisibility::Unit],
                ['name' => 'Legacy DNS Migration', 'identifier' => 'DNS', 'unit' => 'Infrastructure', 'status' => ProjectStatus::Active, 'visibility' => ProjectVisibility::Workspace],
                ['name' => 'Incident Review 2026-10', 'identifier' => 'IR26', 'unit' => 'Security', 'status' => ProjectStatus::Active, 'visibility' => ProjectVisibility::Private],
            ]
            : [
                ['name' => 'Customer Onboarding Refresh', 'identifier' => 'ONB', 'unit' => 'Customer Success', 'status' => ProjectStatus::Active, 'visibility' => ProjectVisibility::Workspace],
                ['name' => 'Quarterly Operations Review', 'identifier' => 'OPS', 'unit' => 'Operations', 'status' => ProjectStatus::Active, 'visibility' => ProjectVisibility::Unit],
                ['name' => 'Budget Planning 2027', 'identifier' => 'FIN', 'unit' => 'Finance', 'status' => ProjectStatus::Draft, 'visibility' => ProjectVisibility::Private],
            ];

        foreach ($projectDefinitions as $definition) {
            $unit = $units[$definition['unit']];
            $project = Project::query()->firstOrCreate(
                ['workspace_id' => $workspace->id, 'identifier' => $definition['identifier']],
                [
                    'unit_id' => $unit->id,
                    'name' => $definition['name'],
                    'description' => 'Data demo untuk menguji alur '.strtolower($definition['name']).'.',
                    'visibility' => $definition['visibility'],
                    'status' => $definition['status'],
                    'project_lead_id' => $users->values()->random()->id,
                    'default_assignee_id' => $users->values()->random()->id,
                    'created_by_id' => $admin->id,
                    'start_date' => now()->subDays(fake()->numberBetween(1, 90))->toDateString(),
                    'target_date' => now()->addDays(fake()->numberBetween(15, 180))->toDateString(),
                    'next_task_number' => 1,
                ],
            );

            if (in_array($definition['identifier'], ['DNS', 'IR26'], true)) {
                $project->update([
                    'archived_at' => now()->subDays(3),
                    'archived_by_id' => $admin->id,
                ]);
            }

            $this->seedProject($project, $users, $admin);
        }
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedProject(Project $project, Collection $users, User $admin): void
    {
        $projectMembers = $users->values()->shuffle()->take(fake()->numberBetween(5, $users->count()));
        $roles = [ProjectRole::ProjectAdmin, ProjectRole::Contributor, ProjectRole::Commenter, ProjectRole::Viewer];

        foreach ($projectMembers as $index => $user) {
            ProjectMembership::query()->firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $user->id],
                [
                    'role' => $roles[$index % count($roles)],
                    'added_by_id' => $admin->id,
                    'joined_at' => now()->subDays(fake()->numberBetween(1, 60)),
                ],
            );
        }

        $labels = collect([
            ['name' => 'Urgent', 'color' => '#C2410C'],
            ['name' => 'Customer-facing', 'color' => '#0369A1'],
            ['name' => 'Technical debt', 'color' => '#6B7280'],
            ['name' => 'Needs review', 'color' => '#7C3AED'],
        ])->map(fn (array $attributes): Label => Label::query()->firstOrCreate(
            ['project_id' => $project->id, 'name' => $attributes['name']],
            ['color' => $attributes['color']],
        ));

        $milestones = collect(['Discovery', 'Implementation', 'Verification'])->map(fn (string $name, int $index): Milestone => Milestone::query()->firstOrCreate(
            ['project_id' => $project->id, 'name' => $name],
            ['target_date' => now()->addDays(($index + 1) * 30)->toDateString()],
        ));

        $moduleNames = ['Authentication', 'Dashboard', 'Notifications', 'Reporting', 'Integrations'];
        $modules = collect($moduleNames)->map(fn (string $name): Module => Module::query()->firstOrCreate(
            ['project_id' => $project->id, 'name' => $name],
            ['description' => 'Ruang lingkup '.$name.' pada project ini.'],
        ));

        $releases = collect([
            ['name' => 'Release 2.4', 'description' => 'Perubahan dan perbaikan bulanan untuk Project ini.', 'target_date' => now()->addDays(14)->toDateString(), 'status' => 'planned'],
            ['name' => 'Release 2.5', 'description' => 'Paket pekerjaan berikutnya yang sedang disiapkan.', 'target_date' => now()->addDays(44)->toDateString(), 'status' => 'draft'],
        ])->mapWithKeys(fn (array $attributes): array => [$attributes['name'] => Release::query()->firstOrCreate(
            ['project_id' => $project->id, 'name' => $attributes['name']],
            $attributes,
        )]);

        $statuses = [
            TaskStatus::Backlog, TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Review,
            TaskStatus::Done, TaskStatus::Blocked, TaskStatus::Canceled,
        ];
        $priorities = [TaskPriority::Urgent, TaskPriority::High, TaskPriority::Medium, TaskPriority::Low, null];
        $tasks = collect();

        for ($number = 1; $number <= 14; $number++) {
            $status = $statuses[($number - 1) % count($statuses)];
            $task = Task::query()->firstOrCreate(
                ['project_id' => $project->id, 'number' => $number],
                [
                    'identifier' => $project->identifier.'-'.$number,
                    'title' => fake()->sentence(fake()->numberBetween(3, 7)),
                    'description' => fake()->paragraph(),
                    'status' => $status,
                    'priority' => $priorities[($number - 1) % count($priorities)],
                    'assignee_id' => $number % 5 === 0 ? null : $users->values()->random()->id,
                    'requester_id' => $users->values()->random()->id,
                    'created_by_id' => $admin->id,
                    'due_date' => $number % 4 === 0 ? now()->subDays($number)->toDateString() : now()->addDays($number * 4)->toDateString(),
                    'blocked_reason' => $status === TaskStatus::Blocked ? 'Menunggu keputusan dari vendor dan akses environment.' : null,
                    'canceled_reason' => $status === TaskStatus::Canceled ? 'Scope dipindahkan ke project lain.' : null,
                    'reopen_reason' => $number === 10 ? 'Dibuka kembali setelah hasil UAT tidak sesuai.' : null,
                    'completion_note' => $status === TaskStatus::Done ? 'Selesai dan sudah diverifikasi oleh reviewer.' : null,
                    'evidence_url' => $status === TaskStatus::Done ? 'https://example.com/evidence/'.$project->identifier.'-'.$number : null,
                    'completed_at' => $status === TaskStatus::Done ? now()->subDays(2) : null,
                    'milestone_id' => $milestones->values()[($number - 1) % $milestones->count()]->id,
                    'module_id' => $modules->values()[($number - 1) % $modules->count()]->id,
                    'release_id' => $number <= 7 ? $releases['Release 2.4']->id : ($number <= 11 ? $releases['Release 2.5']->id : null),
                ],
            );

            if ($task->module_id === null) {
                $task->update(['module_id' => $modules->values()[($number - 1) % $modules->count()]->id]);
            }

            $tasks->push($task);
            $task->labels()->syncWithoutDetaching($labels->values()->slice(0, ($number % 3) + 1)->pluck('id')->all());

            if ($number <= 6 && ! $task->comments()->exists()) {
                Comment::query()->create([
                    'task_id' => $task->id,
                    'user_id' => $users->values()->random()->id,
                    'body' => fake()->sentence(),
                ]);
                Comment::query()->create([
                    'task_id' => $task->id,
                    'user_id' => $admin->id,
                    'body' => fake()->sentence(),
                    'edited_at' => $number === 2 ? now()->subDay() : null,
                ]);
            }

            if ($status === TaskStatus::Done && ! $task->attachments()->exists()) {
                Attachment::query()->create([
                    'task_id' => $task->id,
                    'uploaded_by_id' => $admin->id,
                    'disk' => 'attachments',
                    'path' => 'tasks/'.$task->id.'/evidence-'.$number.'.txt',
                    'original_name' => 'evidence-'.$number.'.txt',
                    'mime_type' => 'text/plain',
                    'size' => 2048 + $number,
                ]);
            }
        }

        $project->update(['next_task_number' => $tasks->max('number') + 2]);

        foreach ([
            [$tasks[0], $tasks[1], TaskRelationType::Blocks],
            [$tasks[2], $tasks[3], TaskRelationType::Related],
            [$tasks[4], $tasks[5], TaskRelationType::Duplicate],
        ] as [$task, $relatedTask, $type]) {
            TaskRelation::query()->firstOrCreate(
                ['task_id' => $task->id, 'related_task_id' => $relatedTask->id, 'type' => $type],
                ['project_id' => $project->id],
            );
        }

        foreach ($tasks->take(4) as $task) {
            if (! ActivityLog::query()->where('subject_id', $task->id)->exists()) {
                ActivityLog::query()->create([
                    'workspace_id' => $project->workspace_id,
                    'actor_id' => $users->values()->random()->id,
                    'subject_type' => 'task',
                    'subject_id' => $task->id,
                    'action' => 'task.updated',
                    'description' => 'Task diperbarui dalam data demo.',
                    'properties' => ['status' => $task->status->value, 'project' => $project->identifier],
                    'created_at' => now()->subHours(fake()->numberBetween(1, 72)),
                ]);
            }
        }

        AuditLog::query()->firstOrCreate(
            ['auditable_type' => Project::class, 'auditable_id' => $project->id, 'action' => 'project.created'],
            [
                'workspace_id' => $project->workspace_id,
                'actor_id' => $admin->id,
                'request_id' => (string) Str::ulid(),
                'before' => null,
                'after' => ['name' => $project->name, 'status' => $project->status->value],
                'created_at' => $project->created_at ?? now(),
            ],
        );

        SavedView::query()->firstOrCreate(
            ['project_id' => $project->id, 'user_id' => $admin->id, 'name' => 'Task terlambat'],
            [
                'workspace_id' => $project->workspace_id,
                'filters' => ['due' => 'overdue', 'status' => ['todo', 'in_progress', 'blocked']],
                'grouping' => 'status',
                'compact' => false,
            ],
        );
    }
}
