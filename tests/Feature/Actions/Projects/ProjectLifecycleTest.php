<?php

use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\RestoreProject;
use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('project creation assigns the creator as project admin and lead', function () {
    $creator = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($creator, 'Engineering', 'Asia/Jakarta');
    $unit = $workspace->units->first();

    $project = app(CreateProject::class)->handle($creator, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $unit->id,
        'visibility' => 'unit',
        'status' => 'draft',
    ]);

    expect($project->project_lead_id)->toBe($creator->id)
        ->and($project->memberships)->toHaveCount(1)
        ->and($project->memberships->first()->role->value)->toBe('project_admin');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'project.created',
        'auditable_id' => $project->id,
    ]);
});

test('only completed or canceled projects can be archived and archived projects can be restored', function () {
    $creator = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($creator, 'Engineering', 'Asia/Jakarta');
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $creator->id,
        'status' => ProjectStatus::Active,
    ]);

    expect(fn () => app(ArchiveProject::class)->handle($creator, $project))
        ->toThrow(ValidationException::class);

    $project->update(['status' => ProjectStatus::Completed]);
    $archived = app(ArchiveProject::class)->handle($creator, $project->fresh());

    expect($archived->archived_at)->not->toBeNull();

    $restored = app(RestoreProject::class)->handle($creator, $archived);

    expect($restored->archived_at)->toBeNull();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'project.restored',
        'auditable_id' => $project->id,
    ]);
});
