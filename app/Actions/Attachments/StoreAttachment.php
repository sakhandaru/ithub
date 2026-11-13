<?php

namespace App\Actions\Attachments;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class StoreAttachment
{
    public function __construct(private RecordActivity $recordActivity, private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Task $task, UploadedFile $file): Attachment
    {
        $disk = (string) config('sitop.attachments.disk');
        $path = $disk.'.'.$task->id.'.'.Str::ulid().'.'.$file->extension();

        try {
            return DB::transaction(function () use ($actor, $task, $file, $disk, $path): Attachment {
                Storage::disk($disk)->putFileAs('tasks/'.$task->id, $file, basename($path));
                $attachment = $task->attachments()->create([
                    'uploaded_by_id' => $actor->getKey(),
                    'disk' => $disk,
                    'path' => 'tasks/'.$task->id.'/'.basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => (string) $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
                $this->recordActivity->handle($task, 'task.attachment.created', $actor, $task->project->workspace, 'Attachment ditambahkan.', ['attachment_id' => $attachment->id, 'name' => $attachment->original_name]);
                $this->recordAuditLog->handle($attachment, 'task.attachment.created', $actor, $task->project->workspace, after: ['task_id' => $task->id, 'original_name' => $attachment->original_name]);

                return $attachment->load('uploader');
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete('tasks/'.$task->id.'/'.basename($path));
            throw $exception;
        }
    }
}
