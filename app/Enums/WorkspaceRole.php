<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case WorkspaceAdmin = 'workspace_admin';
    case Member = 'member';
    case Guest = 'guest';

    public function canManageWorkspace(): bool
    {
        return $this === self::Owner || $this === self::WorkspaceAdmin;
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::WorkspaceAdmin => 'Workspace Admin',
            self::Member => 'Member',
            self::Guest => 'Guest',
        };
    }
}
