<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\User;

test('workspace dashboard shows visible project and task health', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, ['name' => 'Portal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id, 'visibility' => 'workspace', 'status' => 'active']);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Review deployment', 'assignee_id' => $owner->id]);
    $task->update(['due_date' => now()->subDay()->toDateString()]);

    $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertInertia(fn ($page) => $page->component('Dashboard')->where('stats.my_tasks', 1)->where('stats.overdue', 1)->where('projects.0.identifier', 'PORTAL')->where('projects.0.overdue_tasks', 1));
});
