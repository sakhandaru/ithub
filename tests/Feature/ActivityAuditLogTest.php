<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\TransitionTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\TaskStatus;
use App\Models\User;

test('task changes record both activity and audit metadata', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Selesaikan deployment']);

    app(TransitionTask::class)->handle($owner, $task, TaskStatus::Todo);

    $this->assertDatabaseHas('activity_logs', ['subject_id' => $task->id, 'action' => 'task.status.changed']);
    $this->assertDatabaseHas('audit_logs', ['auditable_id' => $task->id, 'action' => 'task.status.changed']);

    expect($task->fresh()->activityLogs)->toHaveCount(2);
});
