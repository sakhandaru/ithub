<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ProjectVisibility;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\SavedView;
use App\Models\User;
use App\Models\WorkspaceMembership;

function createSavedViewProject(User $owner): Project
{
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');

    return Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $owner->id,
        'visibility' => ProjectVisibility::Workspace,
    ]);
}

function savedViewPayload(string $name = 'Task saya'): array
{
    return [
        'name' => $name,
        'filters' => ['status' => 'blocked', 'sort' => 'due_date', 'direction' => 'asc'],
        'grouping' => 'status',
        'compact' => true,
    ];
}

test('a user can list only their own saved views in a visible project', function () {
    $owner = User::factory()->create();
    $project = createSavedViewProject($owner);
    $otherUser = User::factory()->create();
    SavedView::factory()->create(['workspace_id' => $project->workspace_id, 'project_id' => $project->id, 'user_id' => $owner->id, 'name' => 'Mine']);
    SavedView::factory()->create(['workspace_id' => $project->workspace_id, 'project_id' => $project->id, 'user_id' => $otherUser->id, 'name' => 'Not mine']);

    $this->actingAs($owner)
        ->getJson(route('workspaces.projects.saved-views.index', [$project->workspace_id, $project->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mine');
});

test('a user can create, update, and delete a saved view', function () {
    $owner = User::factory()->create();
    $project = createSavedViewProject($owner);

    $this->actingAs($owner)
        ->post(route('workspaces.projects.saved-views.store', [$project->workspace_id, $project->id]), savedViewPayload())
        ->assertRedirect();

    $savedView = SavedView::query()->whereBelongsTo($owner, 'user')->firstOrFail();
    $this->assertDatabaseHas('saved_views', ['id' => $savedView->id, 'name' => 'Task saya', 'grouping' => 'status', 'compact' => true]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'saved_view.created', 'auditable_id' => $savedView->id]);
    $this->assertDatabaseHas('activity_logs', ['action' => 'saved_view.created', 'subject_id' => $project->id]);

    $this->actingAs($owner)
        ->patch(route('workspaces.projects.saved-views.update', [$project->workspace_id, $project->id, $savedView]), savedViewPayload('Task selesai'))
        ->assertRedirect();
    expect($savedView->refresh()->name)->toBe('Task selesai');
    $this->assertDatabaseHas('audit_logs', ['action' => 'saved_view.updated', 'auditable_id' => $savedView->id]);

    $this->actingAs($owner)
        ->delete(route('workspaces.projects.saved-views.destroy', [$project->workspace_id, $project->id, $savedView]))
        ->assertRedirect();
    $this->assertDatabaseMissing('saved_views', ['id' => $savedView->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'saved_view.deleted', 'auditable_id' => $project->id]);
});

test('saved view endpoints enforce visibility and ownership', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = createSavedViewProject($owner);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $project->workspace_id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member,
    ]);
    $savedView = SavedView::factory()->create(['workspace_id' => $project->workspace_id, 'project_id' => $project->id, 'user_id' => $owner->id]);

    $this->actingAs($member)
        ->getJson(route('workspaces.projects.saved-views.index', [$project->workspace_id, $project->id]))
        ->assertOk()
        ->assertJsonPath('data', []);

    $this->actingAs($member)
        ->delete(route('workspaces.projects.saved-views.destroy', [$project->workspace_id, $project->id, $savedView]))
        ->assertForbidden();
    $this->assertDatabaseHas('saved_views', ['id' => $savedView->id]);
});

test('saved view validation rejects duplicate names and unsupported filters', function () {
    $owner = User::factory()->create();
    $project = createSavedViewProject($owner);
    SavedView::factory()->create(['workspace_id' => $project->workspace_id, 'project_id' => $project->id, 'user_id' => $owner->id, 'name' => 'Existing']);

    $this->actingAs($owner)
        ->post(route('workspaces.projects.saved-views.store', [$project->workspace_id, $project->id]), [
            ...savedViewPayload('Existing'),
            'filters' => ['status' => 'blocked', 'unsupported' => true],
        ])
        ->assertSessionHasErrors(['name', 'filters']);
});
