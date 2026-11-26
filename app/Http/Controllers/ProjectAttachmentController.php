<?php

namespace App\Http\Controllers;

use App\Actions\Attachments\DeleteProjectAttachment;
use App\Actions\Attachments\StoreProjectAttachment;
use App\Http\Requests\StoreProjectAttachmentRequest;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectAttachmentController extends Controller
{
    public function index(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);
        $project->load('unit');

        return Inertia::render('projects/Files', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'identifier' => $project->identifier,
                'archived_at' => $project->archived_at?->toIso8601String(),
            ],
            'files' => Attachment::query()
                ->where(function ($query) use ($project): void {
                    $query->whereBelongsTo($project)->orWhereHas('task', fn ($taskQuery) => $taskQuery->whereBelongsTo($project));
                })
                ->whereNotNull('task_id')
                ->with(['uploader', 'task'])
                ->latest()
                ->get()
                ->map(fn ($attachment): array => [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'task' => $attachment->task?->only(['id', 'identifier', 'title']),
                    'mime_type' => $attachment->mime_type,
                    'size' => $attachment->size,
                    'created_at' => $attachment->created_at?->toIso8601String(),
                    'uploader' => $attachment->uploader?->only(['id', 'name', 'email']),
                ])->values(),
            'canManage' => Gate::allows('update', $project),
        ]);
    }

    public function store(StoreProjectAttachmentRequest $request, Workspace $workspace, Project $project, StoreProjectAttachment $storeProjectAttachment): RedirectResponse
    {
        $storeProjectAttachment->handle($request->user(), $project, $request->file('file'));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Berkas Project ditambahkan.']);

        return back();
    }

    public function download(Workspace $workspace, Project $project, Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function preview(Workspace $workspace, Project $project, Attachment $attachment): \Symfony\Component\HttpFoundation\Response
    {
        Gate::authorize('view', $attachment);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    public function destroy(Workspace $workspace, Project $project, Attachment $attachment, DeleteProjectAttachment $deleteProjectAttachment): RedirectResponse
    {
        Gate::authorize('delete', $attachment);
        $deleteProjectAttachment->handle(request()->user(), $attachment);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Berkas Project dihapus.']);

        return back();
    }
}
