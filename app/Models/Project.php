<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use Database\Factories\ProjectFactory;
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
    'workspace_id',
    'unit_id',
    'name',
    'identifier',
    'description',
    'visibility',
    'status',
    'project_lead_id',
    'default_assignee_id',
    'created_by_id',
    'start_date',
    'target_date',
    'next_task_number',
    'archived_at',
    'archived_by_id',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'visibility' => ProjectVisibility::class,
            'status' => ProjectStatus::class,
            'start_date' => 'immutable_date',
            'target_date' => 'immutable_date',
            'archived_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function projectLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_lead_id');
    }

    /** @return BelongsTo<User, $this> */
    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /** @return BelongsTo<User, $this> */
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_id');
    }

    /** @return HasMany<ProjectMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_memberships')
            ->withPivot(['id', 'role', 'added_by_id', 'joined_at'])
            ->withTimestamps();
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'auditable_id')
            ->where('auditable_type', self::class);
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return HasMany<Label, $this> */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    /** @return HasMany<Milestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    /** @return HasMany<TaskRelation, $this> */
    public function taskRelations(): HasMany
    {
        return $this->hasMany(TaskRelation::class);
    }

    /** @return HasMany<SavedView, $this> */
    public function savedViews(): HasMany
    {
        return $this->hasMany(SavedView::class);
    }

    /** @return HasMany<ActivityLog, $this> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'subject_id')
            ->where('subject_type', self::class);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $visibleQuery) use ($user): void {
            $visibleQuery
                ->whereHas('workspace.memberships', function (Builder $membershipQuery) use ($user): void {
                    $membershipQuery->whereBelongsTo($user)
                        ->whereIn('role', ['owner', 'workspace_admin']);
                })
                ->orWhere(function (Builder $workspaceQuery) use ($user): void {
                    $workspaceQuery->where('visibility', ProjectVisibility::Workspace->value)
                        ->whereHas('workspace.memberships', fn (Builder $membershipQuery) => $membershipQuery->whereBelongsTo($user));
                })
                ->orWhere(function (Builder $unitQuery) use ($user): void {
                    $unitQuery->where('visibility', ProjectVisibility::Unit->value)
                        ->whereHas('unit.memberships', fn (Builder $membershipQuery) => $membershipQuery->whereBelongsTo($user));
                })
                ->orWhere(function (Builder $privateQuery) use ($user): void {
                    $privateQuery->where('visibility', ProjectVisibility::Private->value)
                        ->whereHas('memberships', fn (Builder $membershipQuery) => $membershipQuery->whereBelongsTo($user));
                });
        });
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
