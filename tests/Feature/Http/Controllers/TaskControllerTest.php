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
    ]);

    $task = Task::query()->firstOrFail();
    $response->assertRedirect();
    expect($task->identifier)->toBe('PORTAL-1');

    $this->actingAs($contributor)
        ->post(route('workspaces.projects.tasks.transition', [$workspace, $project, $task]), [
            'status' => 'todo',
        ])
        ->assertRedirect();

    expect($task->fresh()->status->value)->toBe('todo');
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

test('task detail page loads the same task context', function () {
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
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Show')
            ->where('task.id', $task->id)
            ->where('task.title', 'Task detail')
            ->where('transitionStatuses.0.value', 'todo'));
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
        ->get(route('workspaces.projects.board', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Board')
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
        ->get(route('workspaces.projects.board', [$workspace, $project]).'?preset=blocked&sort=title&direction=asc')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Board')
            ->has('tasks', 1)
            ->where('tasks.0.id', $blockedTask->id)
            ->where('filters.preset', 'blocked')
            ->where('filters.sort', 'title')
            ->where('filters.direction', 'asc'));
});
