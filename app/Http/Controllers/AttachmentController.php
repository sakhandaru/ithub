<?php

namespace App\Http\Controllers;

use App\Actions\Attachments\DeleteAttachment;
use App\Actions\Attachments\StoreAttachment;
use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, Workspace $workspace, Project $project, Task $task, StoreAttachment $storeAttachment): RedirectResponse
    {
        $storeAttachment->handle($request->user(), $task, $request->file('file'));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attachment ditambahkan.']);

        return back();
    }

    public function download(Workspace $workspace, Project $project, Task $task, Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Workspace $workspace, Project $project, Task $task, Attachment $attachment, DeleteAttachment $deleteAttachment): RedirectResponse
    {
        Gate::authorize('delete', $attachment);
        $deleteAttachment->handle(request()->user(), $attachment);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attachment dihapus.']);

        return back();
    }
}
