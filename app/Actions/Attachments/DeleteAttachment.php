<?php

namespace App\Actions\Attachments;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DeleteAttachment
{
    public function __construct(private RecordActivity $recordActivity, private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Attachment $attachment): void
    {
        DB::transaction(function () use ($actor, $attachment): void {
            $task = $attachment->task;
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
            $this->recordActivity->handle($task, 'task.attachment.deleted', $actor, $task->project->workspace, 'Attachment dihapus.', ['attachment_id' => $attachment->id]);
            $this->recordAuditLog->handle($task, 'task.attachment.deleted', $actor, $task->project->workspace, after: ['attachment_id' => $attachment->id]);
        });
    }
}
