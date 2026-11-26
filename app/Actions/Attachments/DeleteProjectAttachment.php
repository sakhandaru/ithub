<?php

namespace App\Actions\Attachments;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DeleteProjectAttachment
{
    public function __construct(private RecordActivity $recordActivity, private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Attachment $attachment): void
    {
        DB::transaction(function () use ($actor, $attachment): void {
            $task = $attachment->task;
            $project = $attachment->project ?? $task?->project;
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
            $this->recordActivity->handle($task ?? $project, $task ? 'task.attachment.deleted' : 'project.file.deleted', $actor, $project->workspace, 'Berkas Project dihapus.', ['attachment_id' => $attachment->id]);
            $this->recordAuditLog->handle($task ?? $project, $task ? 'task.attachment.deleted' : 'project.file.deleted', $actor, $project->workspace, after: ['attachment_id' => $attachment->id]);
        });
    }
}
