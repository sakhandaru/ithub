<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['project_id', 'author_id', 'health', 'title', 'period_start', 'period_end', 'summary', 'accomplishments', 'risks', 'decisions_needed', 'next_steps', 'next_update_at'])]
class ProjectUpdate extends Model
{
    use HasUlids;

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_update_mentions');
    }
}
