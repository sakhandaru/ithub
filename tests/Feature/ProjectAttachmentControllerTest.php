<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('project admin can upload and download a project file', function () {
    Storage::fake('attachments');
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
        ->post(route('workspaces.projects.files.store', [$workspace, $project]), [
            'file' => UploadedFile::fake()->createWithContent('proposal.txt', 'project proposal'),
        ])
        ->assertRedirect();

    $attachment = $project->attachments()->firstOrFail();
    Storage::disk('attachments')->assertExists($attachment->path);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.files.download', [$workspace, $project, $attachment]))
        ->assertOk();
});

test('project files cannot be uploaded with unsupported extensions', function () {
    Storage::fake('attachments');
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
        ->post(route('workspaces.projects.files.store', [$workspace, $project]), [
            'file' => UploadedFile::fake()->createWithContent('malware.exe', 'bad'),
        ])
        ->assertSessionHasErrors('file');

    $this->assertDatabaseCount('attachments', 0);
});

test('task attachments appear in the project files page', function () {
    Storage::fake('attachments');
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Attach evidence']);

    $this->actingAs($owner)
        ->post(route('workspaces.projects.tasks.attachments.store', [$workspace, $project, $task]), [
            'file' => UploadedFile::fake()->createWithContent('evidence.txt', 'task evidence'),
        ])
        ->assertRedirect();

    $this->actingAs($owner)
        ->get(route('workspaces.projects.files.index', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('projects/Files')->has('files', 1)->where('files.0.original_name', 'evidence.txt'));
});
