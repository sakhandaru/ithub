<?php

namespace App\Models;

use App\Enums\TaskRelationType;
use Database\Factories\TaskRelationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'task_id', 'related_task_id', 'type'])]
class TaskRelation extends Model
{
    /** @use HasFactory<TaskRelationFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return ['type' => TaskRelationType::class];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<Task, $this> */
    public function relatedTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'related_task_id');
    }
}
