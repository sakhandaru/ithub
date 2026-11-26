<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectUpdateRequest;
use App\Models\Project;
use App\Models\Workspace;
use App\Notifications\TaskNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectUpdateController extends Controller
{
    public function index(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/Updates', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => ['id' => $project->id, 'name' => $project->name, 'identifier' => $project->identifier, 'archived' => $project->isArchived()],
            'members' => $project->memberships()->with('user:id,name,avatar')->get()->map(fn ($membership): array => ['id' => $membership->user->id, 'name' => $membership->user->name, 'avatar' => $membership->user->avatar])->values(),
            'updates' => $project->updates()->with(['author:id,name', 'mentionedUsers:id,name'])->latest()->get()->map(fn ($update): array => [
                'id' => $update->id, 'title' => $update->title, 'health' => $update->health, 'period_start' => $update->period_start, 'period_end' => $update->period_end, 'summary' => $update->summary,
                'accomplishments' => $update->accomplishments, 'risks' => $update->risks, 'decisions_needed' => $update->decisions_needed, 'next_steps' => $update->next_steps, 'next_update_at' => $update->next_update_at, 'created_at' => $update->created_at->toIso8601String(), 'author' => $update->author?->only(['id', 'name']), 'mentioned_users' => $update->mentionedUsers->map(fn ($user): array => ['id' => $user->id, 'name' => $user->name])->values()->all(),
            ])->values(),
            'canCreate' => Gate::allows('update', $project) && ! $project->isArchived(),
        ]);
    }

    public function store(StoreProjectUpdateRequest $request, Workspace $workspace, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $mentionedUserIds = $data['mentioned_user_ids'] ?? [];
        $update = $project->updates()->create(Arr::except($data, ['mentioned_user_ids']) + ['author_id' => $request->user()->id]);
        $update->mentionedUsers()->sync($mentionedUserIds);

        $url = route('workspaces.projects.updates', [$workspace, $project]);
        $update->mentionedUsers()->get()->reject(fn ($user): bool => $user->is($request->user()))->each(fn ($user): mixed => $user->notify(new TaskNotification('mention', 'Anda ditandai dalam Update Project', $project->identifier.' · Update baru oleh '.$request->user()->name, $url)));

        return back()->with('toast', ['type' => 'success', 'message' => 'Update Project dibuat.']);
    }
}
