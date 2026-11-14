<?php

namespace App\Http\Controllers;

use App\Actions\SavedViews\CreateSavedView;
use App\Actions\SavedViews\DeleteSavedView;
use App\Actions\SavedViews\UpdateSavedView;
use App\Http\Requests\StoreSavedViewRequest;
use App\Http\Requests\UpdateSavedViewRequest;
use App\Models\Project;
use App\Models\SavedView;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SavedViewController extends Controller
{
    public function index(Workspace $workspace, Project $project): JsonResponse
    {
        Gate::authorize('viewAny', [SavedView::class, $project]);
        /** @var User $user */
        $user = request()->user();

        $savedViews = SavedView::query()
            ->forProject($project)
            ->forUser($user)
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $savedViews->map(fn (SavedView $savedView): array => $this->payload($savedView))->values()]);
    }

    public function store(StoreSavedViewRequest $request, Workspace $workspace, Project $project, CreateSavedView $createSavedView): RedirectResponse
    {
        $createSavedView->handle($request->user(), $project, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved View dibuat.']);

        return back();
    }

    public function update(UpdateSavedViewRequest $request, Workspace $workspace, Project $project, SavedView $savedView, UpdateSavedView $updateSavedView): RedirectResponse
    {
        $updateSavedView->handle($request->user(), $savedView, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved View diperbarui.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, SavedView $savedView, DeleteSavedView $deleteSavedView): RedirectResponse
    {
        Gate::authorize('delete', $savedView);
        $deleteSavedView->handle(request()->user(), $savedView);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved View dihapus.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function payload(SavedView $savedView): array
    {
        return [
            'id' => $savedView->id,
            'name' => $savedView->name,
            'filters' => $savedView->filters,
            'grouping' => $savedView->grouping,
            'compact' => $savedView->compact,
            'created_at' => $savedView->created_at?->toIso8601String(),
            'updated_at' => $savedView->updated_at?->toIso8601String(),
        ];
    }
}
