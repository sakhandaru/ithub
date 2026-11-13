<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $workspace = $user->activeWorkspace;
        $query = trim($request->string('q')->toString());
        $projects = collect();
        $tasks = collect();
        if ($workspace && $query !== '') {
            $projects = Project::query()->whereBelongsTo($workspace)->visibleTo($user)->whereNull('archived_at')->where(fn ($builder) => $builder->where('name', 'like', "%{$query}%")->orWhere('identifier', 'like', "%{$query}%"))->limit(10)->get(['id', 'name', 'identifier']);
            $tasks = Task::query()->whereHas('project', fn ($builder) => $builder->whereBelongsTo($workspace)->visibleTo($user)->whereNull('archived_at'))->with('project:id,name,identifier')->where(fn ($builder) => $builder->where('identifier', 'like', "%{$query}%")->orWhere('title', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"))->limit(20)->get(['id', 'project_id', 'identifier', 'title', 'status']);
        }

        return Inertia::render('Search', ['workspace' => $workspace?->only(['id', 'name']), 'query' => $query, 'projects' => $projects, 'tasks' => $tasks->map(fn (Task $task): array => ['id' => $task->id, 'identifier' => $task->identifier, 'title' => $task->title, 'project' => $task->project?->only(['id', 'name', 'identifier']), 'status' => (string) $task->getRawOriginal('status')])->values()]);
    }
}
