<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\TaskStatus;
use App\Models\User;

test('project dashboard renders project command center data', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $blockedTask = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task blocked']);
    $blockedTask->update(['status' => TaskStatus::Blocked, 'due_date' => now()->subDay()]);
    app(CreateTask::class)->handle($owner, $project, ['title' => 'Task backlog']);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.dashboard', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/Dashboard')
            ->where('stats.total', 2)
            ->where('stats.blocked', 1)
            ->where('stats.overdue', 1)
            ->where('stats.without_assignee', 2)
            ->where('stats.without_deadline', 1)
            ->has('stats.status_counts', 7)
            ->where('health', 'off_track')
            ->has('next_tasks', 2)
            ->where('next_milestone', null)
            ->has('activities'));
});
test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
