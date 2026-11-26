<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\SavedView;
use App\Models\Task;
use App\Models\User;

test('a contributor can create and transition a task through project routes', function () {
    $owner = User::factory()->create();
    $contributor = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $workspace->memberships()->create(['user_id' => $contributor->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $project->memberships()->create(['user_id' => $contributor->id, 'role' => 'contributor', 'joined_at' => now()]);

    $response = $this->actingAs($contributor)->post(route('workspaces.projects.tasks.store', [$workspace, $project]), [
        'title' => 'Dokumentasikan perubahan',
        'expected_outcome' => 'Dokumentasi tersedia untuk tim.',
        'start_date' => '2026-11-18',
        'due_date' => '2026-11-20',
    ]);

    $task = Task::query()->firstOrFail();
    $response->assertRedirect();
    expect($task->identifier)->toBe('PORTAL-1')
        ->and($task->expected_outcome)->toBe('Dokumentasi tersedia untuk tim.')
        ->and($task->start_date->toDateString())->toBe('2026-11-18')
        ->and($task->due_date->toDateString())->toBe('2026-11-20');

    $this->actingAs($contributor)
        ->post(route('workspaces.projects.tasks.transition', [$workspace, $project, $task]), [
            'status' => 'todo',
        ])
        ->assertRedirect();

    expect($task->fresh()->status->value)->toBe('todo');
});

test('tasks can be assigned to and filtered by a project release', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $release = $project->releases()->create(['name' => 'Rilis 1.0', 'status' => 'planned']);
    $otherRelease = $project->releases()->create(['name' => 'Rilis 2.0', 'status' => 'draft']);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task rilis', 'release_id' => $release->id]);
    app(CreateTask::class)->handle($owner, $project, ['title' => 'Task lain', 'release_id' => $otherRelease->id]);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?release='.$release->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id)
            ->where('filters.release', $release->id));
});

test('a task cannot be assigned to a release from another project', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $otherProject = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Infra Internal', 'identifier' => 'INFRA', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $release = $otherProject->releases()->create(['name' => 'Rilis lain', 'status' => 'draft']);

    $this->actingAs($owner)
        ->post(route('workspaces.projects.tasks.store', [$workspace, $project]), ['title' => 'Task invalid', 'release_id' => $release->id])
        ->assertSessionHasErrors('release_id');

    expect(Task::query()->where('title', 'Task invalid')->exists())->toBeFalse();
});

test('task route does not expose a task from another project', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $otherProject = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Infra Internal',
        'identifier' => 'INFRA',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $otherProject, ['title' => 'Task lain']);

    $this->actingAs($owner)
        ->patch(route('workspaces.projects.tasks.update', [$workspace, $project, $task]), ['title' => 'Tidak boleh'])
        ->assertNotFound();
});

test('project admin can bulk update task priority and PIC', function () {
    $owner = User::factory()->create();
    $contributor = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $workspace->memberships()->create(['user_id' => $contributor->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $project->memberships()->create(['user_id' => $contributor->id, 'role' => 'contributor', 'joined_at' => now()]);
    $first = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task pertama']);
    $second = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task kedua']);

    $this->actingAs($owner)
        ->post(route('workspaces.projects.tasks.bulk-update', [$workspace, $project]), [
            'task_ids' => [$first->id, $second->id],
            'priority' => 'high',
            'assignee_id' => $contributor->id,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($first->fresh()->priority->value)->toBe('high')
        ->and($first->fresh()->assignee_id)->toBe($contributor->id)
        ->and($second->fresh()->priority->value)->toBe('high')
        ->and($second->fresh()->assignee_id)->toBe($contributor->id);
});

test('task list filters by search and exposes a selected side peek task', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Monitoring produksi']);
    app(CreateTask::class)->handle($owner, $project, ['title' => 'Dokumentasi internal']);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?search=monitoring&task='.$task->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->has('tasks', 1)
            ->where('selectedTask.id', $task->id)
            ->where('taskDetail.task.id', $task->id)
            ->where('taskDetail.task.title', 'Monitoring produksi')
            ->where('filters.search', 'monitoring'));
});

test('task list supports triage presets', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $blockedTask = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task terblokir']);
    $blockedTask->update(['status' => 'blocked']);
    app(CreateTask::class)->handle($owner, $project, ['title' => 'Task biasa']);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?preset=blocked')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $blockedTask->id)
            ->where('filters.preset', 'blocked'));
});

test('task list supports the current week preset', function () {
    $this->travelTo('2026-11-16 10:00:00');

    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $thisWeekTask = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task minggu ini']);
    $thisWeekTask->update(['due_date' => '2026-11-18']);
    $nextWeekTask = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task minggu depan']);
    $nextWeekTask->update(['due_date' => '2026-11-23']);

    $response = $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?preset=week');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $thisWeekTask->id)
            ->where('filters.preset', 'week'));

    $this->travelBack();
});

test('task list supports the no deadline preset', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $noDeadlineTask = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task tanpa deadline']);
    $scheduledTask = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task terjadwal']);
    $scheduledTask->update(['due_date' => '2026-11-20']);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?preset=no_deadline')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $noDeadlineTask->id)
            ->where('filters.preset', 'no_deadline'));
});

test('task views render from the shared task page', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->where('view', 'board'));

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?view=list')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->where('view', 'list'));
});

test('task list exposes only the current users saved view as active', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $savedView = SavedView::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'user_id' => $owner->id,
    ]);
    $otherView = SavedView::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?view='.$savedView->id)
        ->assertInertia(fn ($page) => $page->where('activeSavedViewId', $savedView->id));

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?view='.$otherView->id)
        ->assertInertia(fn ($page) => $page->where('activeSavedViewId', null));
});

test('task list ignores unavailable saved view filters with feedback', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    app(CreateTask::class)->handle($owner, $project, ['title' => 'Task tetap terlihat']);
    $savedView = SavedView::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'filters' => ['status' => 'removed-status'],
    ]);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?view='.$savedView->id.'&status=removed-status')
        ->assertInertia(fn ($page) => $page
            ->has('tasks', 1)
            ->where('filters.status', '')
            ->where('savedViewWarnings.0', 'status'));
});

test('task detail route opens the task side peek', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task detail']);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.show', [$workspace, $project, $task]))
        ->assertRedirect(route('workspaces.projects.tasks.index', [
            'workspace' => $workspace,
            'project' => $project,
            'task' => $task->id,
        ]));
});

test('board groups tasks by workflow status', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Board task']);
    $task->update(['status' => 'in_progress']);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?view=board')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->where('view', 'board')
            ->where('tasks.0.status', 'in_progress'));
});

test('board preserves and applies task list filters', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $blockedTask = app(CreateTask::class)->handle($owner, $project, ['title' => 'Blocked board task']);
    $blockedTask->update(['status' => 'blocked']);
    app(CreateTask::class)->handle($owner, $project, ['title' => 'Other board task']);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?view=board&preset=blocked&sort=title&direction=asc')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->where('view', 'board')
            ->has('tasks', 1)
            ->where('tasks.0.id', $blockedTask->id)
            ->where('filters.preset', 'blocked')
            ->where('filters.sort', 'title')
            ->where('filters.direction', 'asc'));
});

test('calendar exposes only tasks with deadlines', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $scheduledTask = app(CreateTask::class)->handle($owner, $project, ['title' => 'Scheduled task']);
    $scheduledTask->update(['start_date' => '2026-11-18', 'due_date' => '2026-11-20']);
    app(CreateTask::class)->handle($owner, $project, ['title' => 'Task tanpa deadline']);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?view=calendar')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->where('view', 'calendar')
            ->has('scheduledTasks', 1)
            ->where('scheduledTasks.0.id', $scheduledTask->id)
            ->where('scheduledTasks.0.start_date', '2026-11-18')
            ->where('scheduledTasks.0.due_date', '2026-11-20')
            ->where('editableTaskIds', fn ($ids): bool => collect($ids)->contains($scheduledTask->id)));
});

test('task update rejects a start date after the deadline', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task rentang tanggal']);
    $task->update(['due_date' => '2026-11-20']);

    $this->actingAs($owner)
        ->from(route('workspaces.projects.tasks.show', [$workspace, $project, $task]))
        ->patch(route('workspaces.projects.tasks.update', [$workspace, $project, $task]), [
            'start_date' => '2026-11-21',
            'due_date' => '2026-11-20',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('start_date');

    expect($task->fresh()->start_date)->toBeNull();
});

test('gantt exposes scheduled tasks and project milestones', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Gantt task']);
    $task->update(['due_date' => '2026-11-20']);
    $milestone = $project->milestones()->create(['name' => 'Pilot', 'target_date' => '2026-11-25']);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.index', [$workspace, $project]).'?view=gantt')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->where('view', 'gantt')
            ->has('scheduledTasks', 1)
            ->where('scheduledTasks.0.id', $task->id)
            ->has('milestones', 1)
            ->where('milestones.0.id', $milestone->id));
});
