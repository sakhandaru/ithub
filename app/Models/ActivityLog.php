<?php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['workspace_id', 'actor_id', 'ip_address', 'subject_type', 'subject_id', 'action', 'description', 'properties'])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array{type: string, label: string, href: string|null}
     */
    public function entityData(Workspace $workspace): array
    {
        $subject = $this->subject;
        $properties = is_array($this->properties) ? $this->properties : [];
        $label = $properties['name'] ?? $this->subject_id;
        $href = null;

        if ($subject instanceof Project) {
            $label = $subject->identifier.' — '.$subject->name;
            $href = route('workspaces.projects.show', [$workspace, $subject]);
        } elseif ($subject instanceof Task) {
            $label = $subject->identifier.' — '.$subject->title;
            $href = route('workspaces.projects.tasks.show', [$workspace, $subject->project_id, $subject]);
        } elseif ($subject) {
            $label = $subject->identifier ?? $subject->name ?? $label;
        }

        return [
            'type' => $subject ? class_basename($subject) : (string) $this->subject_type,
            'label' => (string) $label,
            'href' => $href,
        ];
    }
}
