<?php

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\WorkspaceRole;

test('domain enums expose the agreed persisted values', function (string $enum, array $values) {
    expect(array_column($enum::cases(), 'value'))->toBe($values);
})->with([
    'workspace roles' => [WorkspaceRole::class, ['owner', 'workspace_admin', 'member', 'guest']],
    'project roles' => [ProjectRole::class, ['project_admin', 'contributor', 'commenter', 'viewer']],
    'project visibility' => [ProjectVisibility::class, ['workspace', 'unit', 'private']],
    'project statuses' => [ProjectStatus::class, ['draft', 'active']],
    'task statuses' => [TaskStatus::class, ['backlog', 'todo', 'in_progress', 'review', 'done', 'blocked', 'canceled']],
    'task priorities' => [TaskPriority::class, ['urgent', 'high', 'medium', 'low']],
]);
