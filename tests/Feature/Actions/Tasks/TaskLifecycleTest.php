<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\TransitionTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ProjectRole;
use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function createTaskProject(User $owner): array
{
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);

    return [$workspace, $project];
}

test('task identifiers increment within a project and apply the default assignee', function () {
    $owner = User::factory()->create();
    $assignee = User::factory()->create();
    [$workspace, $project] = createTaskProject($owner);
    $workspace->memberships()->create(['user_id' => $assignee->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $project->memberships()->create(['user_id' => $assignee->id, 'role' => ProjectRole::Contributor, 'joined_at' => now()]);
    $project->update(['default_assignee_id' => $assignee->id]);

    $first = app(CreateTask::class)->handle($owner, $project, ['title' => 'Pasang monitoring']);
    $second = app(CreateTask::class)->handle($owner, $project, ['title' => 'Uji backup']);

    expect($first->identifier)->toBe('PORTAL-1')
        ->and($second->identifier)->toBe('PORTAL-2')
        ->and($first->status)->toBe(TaskStatus::Backlog)
        ->and($first->assignee_id)->toBe($assignee->id)
        ->and($first->requester_id)->toBe($owner->id);
});

test('task transitions enforce workflow reasons and completion evidence', function () {
    $owner = User::factory()->create();
    [, $project] = createTaskProject($owner);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Rilis perubahan']);

    expect(fn () => app(TransitionTask::class)->handle($owner, $task, TaskStatus::InProgress))
        ->toThrow(ValidationException::class);

    app(TransitionTask::class)->handle($owner, $task, TaskStatus::Todo);
    app(TransitionTask::class)->handle($owner, $task->fresh(), TaskStatus::InProgress);
    expect(fn () => app(TransitionTask::class)->handle($owner, $task->fresh(), TaskStatus::Blocked))
        ->toThrow(ValidationException::class);

    app(TransitionTask::class)->handle($owner, $task->fresh(), TaskStatus::Blocked, 'Menunggu akses firewall');
    app(TransitionTask::class)->handle($owner, $task->fresh(), TaskStatus::InProgress, 'Akses sudah tersedia');
    app(TransitionTask::class)->handle($owner, $task->fresh(), TaskStatus::InReview);

    expect(fn () => app(TransitionTask::class)->handle($owner, $task->fresh(), TaskStatus::Done))
        ->toThrow(ValidationException::class);

    $done = app(TransitionTask::class)->handle($owner, $task->fresh(), TaskStatus::Done, null, 'Perubahan sudah diuji');

    expect($done->isCompleted())->toBeTrue()
        ->and($done->completed_at)->not->toBeNull();
});
