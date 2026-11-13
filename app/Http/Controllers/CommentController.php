<?php

namespace App\Http\Controllers;

use App\Actions\Comments\CreateComment;
use App\Actions\Comments\DeleteComment;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Workspace $workspace, Project $project, Task $task, CreateComment $createComment): RedirectResponse
    {
        $createComment->handle($request->user(), $task, $request->string('body')->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komentar ditambahkan.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, Task $task, Comment $comment, DeleteComment $deleteComment): RedirectResponse
    {
        Gate::authorize('delete', $comment);
        $deleteComment->handle(request()->user(), $comment);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komentar dihapus.']);

        return back();
    }
}
