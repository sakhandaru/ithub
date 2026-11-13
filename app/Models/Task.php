<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id', 'number', 'identifier', 'title', 'description', 'status', 'priority',
    'assignee_id', 'requester_id', 'created_by_id', 'due_date', 'blocked_reason',
    'canceled_reason', 'reopen_reason', 'completion_note', 'evidence_url', 'completed_at',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'immutable_date',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /** @return BelongsToMany<Label, $this> */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'label_task');
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return HasMany<Attachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /** @return HasMany<ActivityLog, $this> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'subject_id')
            ->where('subject_type', self::class);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('project', fn (Builder $projectQuery) => $projectQuery->visibleTo($user));
    }

    public function isCompleted(): bool
    {
        return $this->getRawOriginal('status') === TaskStatus::Done->value;
    }
}
