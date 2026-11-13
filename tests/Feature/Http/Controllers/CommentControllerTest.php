<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\User;

test('commenter can add a comment but cannot update the task', function () {
    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $workspace->memberships()->create(['user_id' => $commenter->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $project->memberships()->create(['user_id' => $commenter->id, 'role' => 'commenter', 'joined_at' => now()]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Review perubahan']);

    $this->actingAs($commenter)
        ->post(route('workspaces.projects.tasks.comments.store', [$workspace, $project, $task]), ['body' => 'Saya sudah review.'])
        ->assertRedirect();

    $this->assertDatabaseHas('comments', ['task_id' => $task->id, 'user_id' => $commenter->id, 'body' => 'Saya sudah review.']);
    $this->assertDatabaseHas('activity_logs', ['action' => 'task.comment.created', 'subject_id' => $task->id]);

    $this->actingAs($commenter)
        ->patch(route('workspaces.projects.tasks.update', [$workspace, $project, $task]), ['title' => 'Tidak boleh'])
        ->assertForbidden();
});

test('comment owner can delete their own comment', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Review perubahan']);
    $comment = $task->comments()->create(['user_id' => $owner->id, 'body' => 'Catatan']);

    $this->actingAs($owner)
        ->delete(route('workspaces.projects.tasks.comments.destroy', [$workspace, $project, $task, $comment]))
        ->assertRedirect();

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});
