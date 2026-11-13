<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\Milestone;
use App\Models\Task;
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

test('a project admin can create milestones and nested subtasks', function () {
    [$owner, $workspace, $project] = structureContext();
    $milestone = Milestone::factory()->create(['project_id' => $project->id]);
    $parent = app(CreateTask::class)->handle($owner, $project, ['title' => 'Parent']);

    $response = $this->actingAs($owner)->post(route('workspaces.projects.tasks.store', [$workspace, $project]), [
        'title' => 'Child', 'parent_task_id' => $parent->id, 'milestone_id' => $milestone->id,
    ]);

    $response->assertRedirect();
    $child = Task::query()->where('title', 'Child')->firstOrFail();
    expect($child->parent_task_id)->toBe($parent->id)->and($child->milestone_id)->toBe($milestone->id);
});

test('task hierarchy rejects self reference and cycles', function () {
    [$owner, $workspace, $project] = structureContext();
    $parent = app(CreateTask::class)->handle($owner, $project, ['title' => 'Parent']);
    $child = app(CreateTask::class)->handle($owner, $project, ['title' => 'Child', 'parent_task_id' => $parent->id]);

    $this->actingAs($owner)->patch(route('workspaces.projects.tasks.update', [$workspace, $project, $parent]), ['parent_task_id' => $parent->id])->assertSessionHasErrors('parent_task_id');
    $this->actingAs($owner)->patch(route('workspaces.projects.tasks.update', [$workspace, $project, $parent]), ['parent_task_id' => $child->id])->assertSessionHasErrors('parent_task_id');
});

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
