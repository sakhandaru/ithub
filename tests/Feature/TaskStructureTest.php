<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\TaskRelation;
use App\Models\User;

function structureContext(): array
{
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);

    return [$owner, $workspace, $project];
}

test('project admins can add and remove task relations', function () {
    [$owner, $workspace, $project] = structureContext();
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'One']);
    $related = app(CreateTask::class)->handle($owner, $project, ['title' => 'Two']);

    $this->actingAs($owner)->post(route('workspaces.projects.tasks.relations.store', [$workspace, $project, $task]), ['related_task_id' => $related->id, 'type' => 'related'])->assertRedirect();
    $relation = TaskRelation::query()->firstOrFail();
    expect($relation->task_id)->toBe($task->id);

    $this->actingAs($owner)->delete(route('workspaces.projects.tasks.relations.destroy', [$workspace, $project, $task, $relation]))->assertRedirect();
    expect(TaskRelation::query()->count())->toBe(0);
});
