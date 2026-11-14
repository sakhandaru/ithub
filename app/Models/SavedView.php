<?php

namespace App\Models;

use Database\Factories\SavedViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workspace_id', 'project_id', 'user_id', 'name', 'filters', 'grouping', 'compact'])]
class SavedView extends Model
{
    /** @use HasFactory<SavedViewFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'compact' => 'boolean',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<SavedView>  $query
     * @return Builder<SavedView>
     */
    #[Scope]
    protected function forProject(Builder $query, Project $project): Builder
    {
        return $query->whereBelongsTo($project);
    }

    /**
     * @param  Builder<SavedView>  $query
     * @return Builder<SavedView>
     */
    #[Scope]
    protected function forUser(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }
}
