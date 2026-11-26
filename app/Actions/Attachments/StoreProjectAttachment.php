<?php

namespace App\Actions\Attachments;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class StoreProjectAttachment
{
    public function __construct(private RecordActivity $recordActivity, private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Project $project, UploadedFile $file): Attachment
    {
        $disk = (string) config('sitop.attachments.disk');
        $filename = $disk.'.'.$project->id.'.'.Str::ulid().'.'.$file->extension();
        $directory = 'projects/'.$project->id;
        $path = $directory.'/'.basename($filename);

        try {
            return DB::transaction(function () use ($actor, $project, $file, $disk, $directory, $path): Attachment {
                Storage::disk($disk)->putFileAs($directory, $file, basename($path));
                $attachment = $project->attachments()->create([
                    'uploaded_by_id' => $actor->getKey(),
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => (string) $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
                $this->recordActivity->handle($project, 'project.file.created', $actor, $project->workspace, 'Berkas Project ditambahkan.', ['attachment_id' => $attachment->id, 'name' => $attachment->original_name]);
                $this->recordAuditLog->handle($attachment, 'project.file.created', $actor, $project->workspace, after: ['project_id' => $project->id, 'original_name' => $attachment->original_name]);

                return $attachment->load('uploader');
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }
}
