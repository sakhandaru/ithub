<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('project commenter can upload and download a private attachment', function () {
    Storage::fake('attachments');
    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $workspace->memberships()->create(['user_id' => $commenter->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $project->memberships()->create(['user_id' => $commenter->id, 'role' => 'commenter', 'joined_at' => now()]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Lampirkan bukti']);

    $this->actingAs($commenter)
        ->post(route('workspaces.projects.tasks.attachments.store', [$workspace, $project, $task]), [
            'file' => UploadedFile::fake()->createWithContent('bukti.txt', 'private evidence'),
        ])
        ->assertRedirect();

    $attachment = $task->attachments()->firstOrFail();
    Storage::disk('attachments')->assertExists($attachment->path);

    $this->actingAs($commenter)
        ->get(route('workspaces.projects.tasks.attachments.download', [$workspace, $project, $task, $attachment]))
        ->assertOk();
});

test('unsupported attachment extensions are rejected', function () {
    Storage::fake('attachments');
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Lampirkan bukti']);

    $this->actingAs($owner)
        ->post(route('workspaces.projects.tasks.attachments.store', [$workspace, $project, $task]), [
            'file' => UploadedFile::fake()->createWithContent('malware.exe', 'bad'),
        ])
        ->assertSessionHasErrors('file');

    $this->assertDatabaseCount('attachments', 0);
});

test('attachment route is isolated from another task', function () {
    Storage::fake('attachments');
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
    $task = app(CreateTask::class)->handle($owner, $otherProject, ['title' => 'Bukti']);
    $attachment = $task->attachments()->create([
        'uploaded_by_id' => $owner->id, 'disk' => 'attachments', 'path' => 'tasks/'.$task->id.'/proof.txt',
        'original_name' => 'proof.txt', 'mime_type' => 'text/plain', 'size' => 5,
    ]);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.tasks.attachments.download', [$workspace, $project, $task, $attachment]))
        ->assertNotFound();
});
